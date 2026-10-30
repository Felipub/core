const {test,expect,login,open,submit}=require('./helpers.cjs');

test('ADM-01: server rejects incomplete admission without creating records',async ({page,fixture})=>{
  await login(page);
  const before=await fixture.state();
  const response=await page.request.post('/modules/Students/applicationFormProcess.php',{form:{firstName:'Incomplete Synthetic'}});
  expect(response.url()).toContain('return=error1');
  const after=await fixture.state();
  expect(after.gibbonApplicationForm).toEqual(before.gibbonApplicationForm);
  expect(after.gibbonPerson).toEqual(before.gibbonPerson);
  expect(after.gibbonFamily).toEqual(before.gibbonFamily);
});

async function fillAdmission(page,today) {
  await open(page,'/modules/Students/applicationForm.php');
  const form=page.locator('#applicationForm');
  const text={firstName:'Admission Synthetic',surname:'Fixture',dob:'15/01/2015',dateStart:today.split('-').reverse().join('/'),homeAddress:'123 Synthetic Street',homeAddressDistrict:'Synthetic District',parent1firstName:'Synthetic Mother',parent1surname:'Fixture',parent1phone1:'1234567890',parent1profession:'Synthetic profession',parent2firstName:'Synthetic Father',parent2surname:'Fixture'};
  for (const [name,value] of Object.entries(text)) {
    const control=form.locator(`[name="${name}"]`);
    await expect(control,`Admission field ${name} must be available to the user`).toBeVisible();
    await control.fill(value);
  }
  const selects={gender:{value:'F'},religion:{label:'Hinduism'},gibbonYearGroupIDEntry:{label:'Beginner'},gibbonFormGroupID:{label:'Open Active'},homeAddressCountry:{label:'India'},parent1gender:{value:'F'},parent1relationship:{label:'Mother'},parent2gender:{value:'M'},parent2relationship:{label:'Father'}};
  for (const [name,value] of Object.entries(selects)) await form.locator(`select[name="${name}"]`).selectOption(value);
  if (await form.locator('select[name="sen"]').count()) await form.locator('select[name="sen"]').selectOption('N');
  // Fill optional configured custom fields explicitly where present in master.
  for (const [name,value] of Object.entries({custom0028:'Synthetic Block',custom0029:'Lucknow',custom0030:'N',custom0031:'Paid',custom0033:'Beginner',custom0011:'not enrolled'})) {
    const control=form.locator(`[name="${name}"]`);
    if (await control.count()) {
      if (await control.evaluate(e=>e.tagName==='SELECT')) await control.selectOption({label:value}); else await control.fill(value);
    }
  }
  const agreement=form.locator('[name="agreement"]');
  if (await agreement.count()) await agreement.check();
  return form;
}

test('ADM-02: public admission persists identity, family and custom fields and is visible to admin',async ({page,fixture})=>{
  const before=await fixture.state();
  const form=await fillAdmission(page,before.fixture.today);
  await submit(page,form);
  const after=await fixture.state();
  expect(after.gibbonApplicationForm).toHaveLength(before.gibbonApplicationForm.length+1);
  const saved=after.gibbonApplicationForm.find(r=>r.firstName==='Admission Synthetic');
  expect(saved).toMatchObject({surname:'Fixture',gender:'F',dob:'2015-01-15',parent1firstName:'Synthetic Mother',parent2firstName:'Synthetic Father',homeAddress:'123 Synthetic Street'});
  expect(+saved.gibbonFormGroupID).toBe(1);
  expect(JSON.parse(saved.fields)).toMatchObject({'0028':'Synthetic Block','0029':'Lucknow','0030':'N','0031':'Paid','0033':'Beginner'});
  expect(after.gibbonPerson).toEqual(before.gibbonPerson);
  await login(page);
  await open(page,'/modules/Students/applicationForm_manage.php');
  await expect(page.locator('body')).toContainText('Admission Synthetic');
  await open(page,'/modules/Students/applicationForm_manage_edit.php',{gibbonApplicationFormID:saved.gibbonApplicationFormID});
  await expect(page.locator('[name="firstName"]')).toHaveValue('Admission Synthetic');
});

test('ADM-03: administrator rejects a pending application without creating an account',async ({page,fixture})=>{
  const {id}=await fixture.candidate();
  await login(page);
  const before=await fixture.state();
  await open(page,'/modules/Students/applicationForm_manage_reject.php',{gibbonApplicationFormID:String(id),gibbonSchoolYearID:'2'});
  await submit(page,page.locator('form').filter({has:page.locator('[name="gibbonApplicationFormID"]')}));
  const after=await fixture.state();
  expect(after.gibbonApplicationForm.find(r=>+r.gibbonApplicationFormID===id).status).toBe('Rejected');
  expect(after.gibbonPerson).toEqual(before.gibbonPerson);
  expect(after.gibbonStudentEnrolment).toEqual(before.gibbonStudentEnrolment);
});

test('ADM-04: accepting a pending application creates its pupil, enrolment and family link',async ({page,fixture})=>{
  const {id}=await fixture.candidate();
  await login(page);
  const before=await fixture.state();
  await open(page,'/modules/Students/applicationForm_manage_accept.php',{gibbonApplicationFormID:String(id),gibbonSchoolYearID:'2'});
  const form=page.locator('form').filter({has:page.locator('[name="informStudent"]')});
  await form.locator('[name="informStudent"]').uncheck();
  await form.locator('[name="informParents"]').uncheck();
  await submit(page,form);
  const after=await fixture.state();
  expect(after.gibbonApplicationForm.find(r=>+r.gibbonApplicationFormID===id).status).toBe('Accepted');
  expect(after.gibbonPerson).toHaveLength(before.gibbonPerson.length+1);
  const pupil=after.gibbonPerson.find(r=>r.firstName==='Review Synthetic');
  expect(pupil).toMatchObject({surname:'Fixture',gender:'F',dob:'2015-01-15'});
  expect(after.gibbonStudentEnrolment.some(r=>r.gibbonPersonID===pupil.gibbonPersonID && +r.gibbonSchoolYearID===2 && +r.gibbonFormGroupID===1)).toBeTruthy();
  expect(after.gibbonFamilyChild.some(r=>r.gibbonPersonID===pupil.gibbonPersonID && +r.gibbonFamilyID===1)).toBeTruthy();
  expect(after.gibbonFamily).toEqual(before.gibbonFamily);
});
