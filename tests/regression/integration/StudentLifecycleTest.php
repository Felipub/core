<?php
namespace Regression\Integration;

use PHPUnit\Framework\TestCase;
use Gibbon\Database\Connection;
use Gibbon\Domain\Students\StudentGateway;
use Gibbon\Domain\User\UserGateway;
use Gibbon\Domain\Attendance\AttendanceLogPersonGateway;

final class StudentLifecycleTest extends TestCase
{
    private $pdo;
    private $students;
    private $db;
    protected function setUp(): void
    {
        regressionPDO(); // Guard before constructing the same connector used by the app.
        $this->db = (new \Gibbon\Database\MySqlConnector())->connect(['databaseServer'=>'db','databaseName'=>'regression','databaseUsername'=>'regression','databasePassword'=>'synthetic-local-only'],true);
        $this->pdo = $this->db->getConnection();
        $this->pdo->beginTransaction();
        $this->students = new StudentGateway($this->db);
    }
    protected function tearDown(): void { if ($this->pdo->inTransaction()) $this->pdo->rollBack(); }
    private function ids($data): array
    {
        $ids = array_map('intval', array_column(iterator_to_array($data), 'gibbonPersonID'));
        sort($ids);
        return $ids;
    }
    public function testActiveRosterIncludesDateBoundariesAndExcludesFutureEndedAndLeft(): void
    {
        $this->assertSame([4,10,13], $this->ids($this->students->queryStudentsBySchoolYear($this->students->newQueryCriteria()->pageSize(100),2)));
        $this->assertSame([4,18], $this->ids($this->students->queryStudentsBySchoolYear($this->students->newQueryCriteria()->pageSize(100),1)));
    }
    public function testTutorAndSearchSelectExactPupils(): void
    {
        $this->assertSame([10], $this->ids($this->students->queryStudentsBySchoolYear($this->students->newQueryCriteria(),2,false,7)));
        $this->assertSame([13], $this->ids($this->students->queryStudentsBySchoolYear($this->students->newQueryCriteria()->searchBy('gibbonPerson.username','pupil13'),2)));
    }
    public function testGroupRosterAndPaginationDoNotLoseOrDuplicateStudents(): void
    {
        $this->assertSame([4,13], $this->ids($this->students->queryStudentEnrolmentByFormGroup($this->students->newQueryCriteria(),1)));
        $ids=[];
        foreach ([1,2,3] as $page) $ids=array_merge($ids,$this->ids($this->students->queryStudentsBySchoolYear($this->students->newQueryCriteria()->sortBy('gibbonPerson.gibbonPersonID')->pageSize(1)->page($page),2)));
        $this->assertSame([4,10,13],$ids);
    }
    public function testEnrolmentMovePreservesPastYearAndOtherFamily(): void
    {
        $current=$this->students->selectBy(['gibbonPersonID'=>4,'gibbonSchoolYearID'=>2])->fetch();
        $this->assertTrue($this->students->update($current['gibbonStudentEnrolmentID'],['gibbonFormGroupID'=>2]));
        $this->assertSame([13],$this->ids($this->students->queryStudentEnrolmentByFormGroup($this->students->newQueryCriteria(),1)));
        $this->assertSame([4,10],$this->ids($this->students->queryStudentEnrolmentByFormGroup($this->students->newQueryCriteria(),2)));
        $this->assertEquals(4,$this->students->selectBy(['gibbonPersonID'=>4,'gibbonSchoolYearID'=>1])->fetch()['gibbonFormGroupID']);
        $this->assertEquals(2,$this->pdo->query('SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=10')->fetchColumn());
    }
    public function testEnrolmentCreateAndDeletePreservesPerson(): void
    {
        $id=$this->students->insert(['gibbonPersonID'=>17,'gibbonSchoolYearID'=>2,'gibbonYearGroupID'=>1,'gibbonFormGroupID'=>2]);
        $this->assertNotFalse($id);
        $this->assertContains(17,$this->ids($this->students->queryStudentEnrolmentByFormGroup($this->students->newQueryCriteria(),2)));
        $this->assertTrue($this->students->delete($id));
        $this->assertEquals(1,$this->pdo->query('SELECT COUNT(*) FROM gibbonPerson WHERE gibbonPersonID=17')->fetchColumn());
    }
    public function testPersonalFieldsPersistWithoutChangingFamilyOrControlPerson(): void
    {
        $users=new UserGateway($this->db);
        $control=$users->getByID(10);
        $this->assertTrue($users->update(4,['preferredName'=>'Updated Synthetic','fields'=>json_encode(['0001'=>'Test value'])]));
        $saved=$users->getByID(4);
        $this->assertSame('Updated Synthetic',$saved['preferredName']);
        $this->assertSame(['0001'=>'Test value'],json_decode($saved['fields'],true));
        $this->assertSame($control,$users->getByID(10));
        $this->assertEquals(1,$this->pdo->query('SELECT gibbonFamilyID FROM gibbonFamilyChild WHERE gibbonPersonID=4')->fetchColumn());
    }
    public function testDashboardTotalsHaveIndependentExpectedValues(): void
    {
        $this->assertEquals(3,$this->students->getStudentEnrolmentCount(2));
        $this->assertEquals(4,$this->students->getStudentTotalGirlsCount(2));
        $this->assertEquals(5,$this->students->getStudentTotalBoysCount(2));
        $this->assertEquals(2,$this->students->getTeachersTotalCount(2));
        foreach (['Passout','Transitioned','Dropout','Migrated'] as $reason) {
            $method='getStudent'.$reason.'Count';
            $this->assertEquals(1,$this->students->$method(2),$reason);
        }
    }
    public function testAttendanceReadWriteByPersonAndDate(): void
    {
        $today=json_decode(file_get_contents('/var/www/html/var/functional-fixture.json'),true)['today'];
        $logs=new AttendanceLogPersonGateway($this->db);
        $record=$logs->selectBy(['gibbonPersonID'=>4,'date'=>$today])->fetch();
        $this->assertTrue($logs->update($record['gibbonAttendanceLogPersonID'],['comment'=>'Synthetic attendance note']));
        $rows=iterator_to_array($logs->queryByPersonAndDate($logs->newQueryCriteria(),4,$today));
        $this->assertCount(1,$rows);
        $this->assertSame('Synthetic attendance note',$rows[0]['comment']);
        $this->assertCount(0,iterator_to_array($logs->queryByPersonAndDate($logs->newQueryCriteria(),4,'2000-01-01')));
    }
    public function testAttendanceTakenPercentageMatchesTwoOfThreePupils(): void
    {
        $today=json_decode(file_get_contents('/var/www/html/var/functional-fixture.json'),true)['today'];
        $this->assertEquals(66.67,$this->students->getAttendanceTakenPercentage(2,$today));
    }
    public function testAttendancePresentPercentageMatchesTwoOfThreePupils(): void
    {
        $today=json_decode(file_get_contents('/var/www/html/var/functional-fixture.json'),true)['today'];
        $this->assertEquals(66.67,$this->students->getAttendancePresentPercentage(2,$today));
    }
}
