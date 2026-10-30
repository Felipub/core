const {test,expect,login,open,submit,healthy}=require('./helpers.cjs');

test('ATT-01: teacher records group attendance and reads back the saved value',async ({page,fixture})=>{
  const before=await fixture.state();
  await login(page,'teacher');
  const params={gibbonFormGroupID:'1',currentDate:before.fixture.today.split('-').reverse().join('/')};
  await open(page,'/modules/Attendance/attendance_take_byFormGroup.php',params);
  const form=page.locator('#attendanceByFormGroup');
  const pupilInput=await form.locator('input[name$="-gibbonPersonID"]').evaluateAll(inputs=>inputs.find(i=>+i.value===4)?.name);
  expect(pupilInput).toBeTruthy();
  const name=pupilInput.replace('-gibbonPersonID','-type');
  await form.locator(`select[name="${name}"]`).selectOption({label:'Absent'});
  await submit(page,form);
  const after=await fixture.state();
  const saved=after.gibbonAttendanceLogPerson.filter(r=>+r.gibbonPersonID===4 && r.date===before.fixture.today);
  expect(saved.some(r=>r.type==='Absent' && +r.gibbonPersonIDTaker===2)).toBeTruthy();
  expect(after.gibbonAttendanceLogPerson.filter(r=>+r.gibbonPersonID===10)).toEqual(before.gibbonAttendanceLogPerson.filter(r=>+r.gibbonPersonID===10));
  await open(page,'/modules/Attendance/attendance_take_byFormGroup.php',params);
  await expect(page.locator(`select[name="${name}"] option:checked`)).toHaveText('Absent');
});

test('PERM-02: tutor with myGroups permission cannot submit another group attendance',async ({page,fixture})=>{
  const before=await fixture.state();
  await login(page,'teacher');
  const response=await page.request.post('/modules/Attendance/attendance_take_byFormGroupProcess.php',{form:{address:'/modules/Attendance/attendance_take_byFormGroup.php',gibbonFormGroupID:'2',currentDate:before.fixture.today.split('-').reverse().join('/'),count:'1','0-gibbonPersonID':'10','0-type':'Absent'}});
  expect(response.url()).toContain('return=error2');
  expect((await fixture.state()).gibbonAttendanceLogPerson).toEqual(before.gibbonAttendanceLogPerson);
});

test('USR-01: identity and photo edit persist without altering family or another pupil',async ({page,fixture})=>{
  await login(page);
  const before=await fixture.state();
  await open(page,'/modules/User Admin/user_manage_edit.php',{gibbonPersonID:'4'});
  const form=page.locator('form').filter({has:page.locator('input[name="file1"]')});
  await form.locator('[name="firstName"]').fill('Updated Synthetic');
  const png=await page.evaluate(()=>{
    const canvas=document.createElement('canvas'); canvas.width=240; canvas.height=320;
    const context=canvas.getContext('2d'); context.fillStyle='#4285ab'; context.fillRect(0,0,240,320);
    return canvas.toDataURL('image/png').split(',')[1];
  });
  await form.locator('input[name="file1"]').setInputFiles({name:'synthetic-photo.png',mimeType:'image/png',buffer:Buffer.from(png,'base64')});
  await submit(page,form);
  const after=await fixture.state();
  const pupil=after.gibbonPerson.find(r=>+r.gibbonPersonID===4);
  expect(pupil.preferredName).toBe('Updated Synthetic');
  expect(pupil.image_240).toMatch(/^uploads\//);
  expect(after.uploads[pupil.image_240].bytes).toBeGreaterThan(0);
  expect(after.gibbonFamilyChild).toEqual(before.gibbonFamilyChild);
  expect(after.gibbonPerson.find(r=>+r.gibbonPersonID===10)).toEqual(before.gibbonPerson.find(r=>+r.gibbonPersonID===10));
  const image=await page.request.get('/'+pupil.image_240);
  expect(image.ok()).toBeTruthy();
  expect(image.headers()['content-type']).toMatch(/^image\//);
  await open(page,'/modules/User Admin/user_manage_edit.php',{gibbonPersonID:'4'});
  await expect(page.locator('[name="firstName"]')).toHaveValue('Updated Synthetic');
});

test('REP-02: printable group report keeps the same active pupils',async ({page},testInfo)=>{
  await login(page);
  await page.goto('/report.php?'+new URLSearchParams({q:'/modules/Students/report_students_byFormGroup.php',gibbonFormGroupID:'1',format:'print'}));
  await healthy(page);
  await expect(page.locator('body')).toContainText('Boundary Boy');
  await expect(page.locator('body')).not.toContainText('Active Boy');
  await expect(page.locator('body')).not.toContainText('Future Girl');
  await expect(page.locator('body')).toContainText('Synthetic, Student');
  await page.emulateMedia({media:'print'});
  await page.screenshot({path:testInfo.outputPath('print.png'),fullPage:true});
});

test('DASH-01: dashboard shows independently known enrolment and gender totals',async ({page})=>{
  await login(page);
  await page.goto('/index.php');
  for (const [label,value] of [['Current Enrolment','3'],['Total Girls','4'],['Total Boys','5'],['Total Teachers','2']]) {
    const card=page.getByText(label,{exact:true}).locator('..');
    await expect(card.locator('div').last()).toHaveText(value);
  }
});

test('REP-03: exported workbook contains the same pupils as the filtered report',async ({page,fixture})=>{
  await login(page);
  const response=await page.request.get('/export.php?'+new URLSearchParams({q:'/modules/Students/report_students_byFormGroup.php',gibbonFormGroupID:'1',format:'export'}));
  expect(response.ok()).toBeTruthy();
  expect(response.headers()['content-disposition']).toContain('.xlsx');
  const rows=await fixture.workbook(await response.body());
  const content=rows.flat().join(' ');
  expect(content).toContain('Boundary Boy');
  expect(content).toContain('Student');
  expect(content).toContain('Synthetic, Student');
  expect(content).not.toContain('Active Boy');
  expect(content).not.toContain('Future Girl');
});

test('USR-02: server refuses an executable extension as a user photograph',async ({page,fixture})=>{
  await login(page);
  const before=await fixture.state();
  await open(page,'/modules/User Admin/user_manage_edit.php',{gibbonPersonID:'4'});
  const form=page.locator('form').filter({has:page.locator('input[name="file1"]')});
  // Submit via the server endpoint to exercise rejection independently of browser accept filters.
  const values=await form.evaluate(f=>Object.fromEntries([...new FormData(f).entries()].filter(([,value])=>typeof value==='string')));
  const response=await page.request.post(await form.getAttribute('action'),{multipart:{...values,file1:{name:'rejected.php',mimeType:'text/plain',buffer:Buffer.from('Synthetic disallowed file')}}});
  expect(response.ok()).toBeTruthy();
  expect(response.url()).toContain('return=success0');
  const after=await fixture.state();
  expect(after.gibbonPerson.find(r=>+r.gibbonPersonID===4).image_240 || '').toBe(before.gibbonPerson.find(r=>+r.gibbonPersonID===4).image_240 || '');
  const rejectedHash=require('node:crypto').createHash('sha256').update('Synthetic disallowed file').digest('hex');
  expect(Object.values(after.uploads).some(file=>file.sha256===rejectedHash)).toBeFalsy();
  expect(Object.keys(after.uploads).some(path=>/(?:rejected|student_240)(?:_\d+)?\.php$/.test(path))).toBeFalsy();
});
