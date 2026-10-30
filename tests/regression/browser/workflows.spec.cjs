const {test,expect,login,open,submit,healthy,rosterIds}=require('./helpers.cjs');

for (const role of ['admin','teacher','staff']) test(`AUTH-${role}: login and logout invalidate protected access`,async ({page})=>{
  await login(page,role);
  await page.goto('/logout.php');
  await open(page,'/modules/Students/studentEnrolment_manage.php');
  await expect(page.locator('input[name="password"]')).toBeVisible();
});

test('PERM-01: teacher cannot administer enrolments or submit a direct change',async ({page,fixture})=>{
  await login(page,'teacher');
  const before=await fixture.state();
  await open(page,'/modules/Students/studentEnrolment_manage.php');
  await expect(page.locator('body')).toContainText('You do not have access to this action.');
  const enrollment=before.gibbonStudentEnrolment.find(r=>+r.gibbonPersonID===4 && +r.gibbonSchoolYearID===2);
  const response=await page.request.post('/modules/Students/studentEnrolment_manage_editProcess.php?gibbonSchoolYearID=2',{form:{address:'/modules/Students/studentEnrolment_manage_edit.php',gibbonStudentEnrolmentID:enrollment.gibbonStudentEnrolmentID,gibbonPersonID:'4',gibbonYearGroupID:'1',gibbonFormGroupID:'2',rollOrder:'1'}});
  expect(response.ok()).toBeTruthy();
  const after=await fixture.state();
  expect(after.gibbonStudentEnrolment).toEqual(before.gibbonStudentEnrolment);
});

test('ENR-01: group change persists and preserves previous year and other pupil',async ({page,fixture})=>{
  await login(page);
  const before=await fixture.state();
  const enrollment=before.gibbonStudentEnrolment.find(r=>+r.gibbonPersonID===4 && +r.gibbonSchoolYearID===2);
  await open(page,'/modules/Students/studentEnrolment_manage_edit.php',{gibbonSchoolYearID:'2',gibbonStudentEnrolmentID:enrollment.gibbonStudentEnrolmentID});
  const form=page.locator('form').filter({has:page.locator('select[name="gibbonFormGroupID"]')});
  await form.locator('select[name="gibbonFormGroupID"]').selectOption({label:'Open Empty'});
  await submit(page,form);
  const after=await fixture.state();
  expect(+after.gibbonStudentEnrolment.find(r=>r.gibbonStudentEnrolmentID===enrollment.gibbonStudentEnrolmentID).gibbonFormGroupID).toBe(2);
  expect(after.gibbonStudentEnrolment.filter(r=>r.gibbonStudentEnrolmentID!==enrollment.gibbonStudentEnrolmentID)).toEqual(before.gibbonStudentEnrolment.filter(r=>r.gibbonStudentEnrolmentID!==enrollment.gibbonStudentEnrolmentID));
  expect(after.gibbonFamilyChild).toEqual(before.gibbonFamilyChild);
  expect(after.gibbonStudentEnrolmentChanged.some(r=>+r.gibbonPersonID===4 && +r.gibbonFormGroupID===2 && +r.gibbonSchoolYearID===2 && r.dateChanged===before.fixture.today)).toBeTruthy();
  await open(page,'/modules/Students/studentEnrolment_manage_edit.php',{gibbonSchoolYearID:'2',gibbonStudentEnrolmentID:enrollment.gibbonStudentEnrolmentID});
  await expect(page.locator('select[name="gibbonFormGroupID"] option:checked')).toHaveText('Open Empty');
});

test('WD-01: withdrawal saves status, reason, date and note without notifications',async ({page,fixture})=>{
  await login(page);
  const before=await fixture.state();
  await open(page,'/modules/Students/student_withdraw.php');
  await page.locator('select[name="gibbonPersonID"]').selectOption({value:before.gibbonPerson.find(r=>+r.gibbonPersonID===4).gibbonPersonID});
  await page.locator('select[name="status"]').selectOption('Left');
  const today=before.fixture.today;
  await page.locator('input[name="dateEnd"]').fill(today.split('-').reverse().join('/'));
  await page.locator('[name="departureReason"]').selectOption('Passout');
  await page.locator('[name="withdrawNote"]').fill('Synthetic withdrawal note');
  await submit(page,page.locator('form').filter({has:page.locator('[name="departureReason"]')}));
  const after=await fixture.state();
  const saved=after.gibbonPerson.find(r=>+r.gibbonPersonID===4);
  expect(saved).toMatchObject({status:'Left',departureReason:'Passout',dateEnd:today});
  expect(after.gibbonStudentNote.some(r=>+r.gibbonPersonID===4 && r.note==='Synthetic withdrawal note')).toBeTruthy();
  expect(after.gibbonPerson.find(r=>+r.gibbonPersonID===10)).toEqual(before.gibbonPerson.find(r=>+r.gibbonPersonID===10));
});

test('WD-02: server rejects withdrawal missing required reason and leaves data intact',async ({page,fixture})=>{
  await login(page);
  const before=await fixture.state();
  const response=await page.request.post('/modules/Students/student_withdrawProcess.php',{form:{gibbonPersonID:'4',status:'Left',dateEnd:before.fixture.today.split('-').reverse().join('/')}});
  expect(response.url()).toContain('return=error1');
  const after=await fixture.state();
  expect(after.gibbonPerson).toEqual(before.gibbonPerson);
  expect(after.gibbonStudentNote).toEqual(before.gibbonStudentNote);
});

test('REP-01: group report contains exactly its active pupils',async ({page})=>{
  await login(page);
  await open(page,'/modules/Students/report_students_byFormGroup.php',{gibbonFormGroupID:'1'});
  await expect(page.locator('body')).toContainText('Boundary Boy');
  await expect(page.locator('body')).toContainText('Student');
  await expect(page.locator('body')).not.toContainText('Active Boy');
  await expect(page.locator('body')).not.toContainText('Future Girl');
  const rows=page.locator('table tbody tr');
  await expect(rows).toHaveCount(2);
  await expect(rows.filter({hasText:'Synthetic, Student'})).toHaveCount(1);
  await expect(rows.filter({hasText:'Fixture, Boundary Boy'})).toHaveCount(1);
});

test('STU-01: student profile shows saved identity and family',async ({page})=>{
  await login(page);
  await open(page,'/modules/Students/student_view_details.php',{gibbonPersonID:'4',subpage:'Overview'});
  await expect(page.locator('body')).toContainText('Student Synthetic');
  await expect(page.locator('body')).toContainText('Synthetic Family');
});

for (const theme of ['Default','Gyansetu']) test(`UI-${theme}: theme supports login and student navigation`,async ({page,fixture},testInfo)=>{
  await fixture.theme(theme);
  await login(page);
  await open(page,'/modules/Students/student_view.php');
  await expect(page.locator('body')).toContainText('Boundary Boy');
  await expect(page.locator('body')).not.toContainText('Future Girl');
  expect(await rosterIds(page)).toEqual([4,10,13]);
  await page.screenshot({path:testInfo.outputPath(`${theme}.png`),fullPage:true});
});
