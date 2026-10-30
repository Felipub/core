<?php
namespace Regression\Unit;
use PHPUnit\Framework\TestCase;
use Gibbon\Services\Format;
use Gibbon\Domain\DataSet;
use Gibbon\Domain\QueryCriteria;

final class WorkflowValuesTest extends TestCase
{
    public function testLeapDaySurvivesFormAndDatabaseRoundTrip(): void
    {
        Format::setup(['dateFormatPHP'=>'d/m/Y']);
        $this->assertSame('29/02/2024',Format::date('2024-02-29'));
        $this->assertSame('2024-02-29',Format::dateConvert('29/02/2024'));
    }
    public function testEmptyOptionalDateIsNotInvented(): void
    {
        $this->assertEmpty(Format::date(''));
        $this->assertEmpty(Format::dateConvert(''));
    }
    public function testSearchAndGroupFilterSurvivePagingAndSerialization(): void
    {
        $criteria=(new QueryCriteria())->searchBy('username','pupil13')->filterBy('formGroup',1)->sortBy('surname')->pageSize(20)->page(2);
        $restored=(new QueryCriteria())->fromJson($criteria->toJson());
        $this->assertSame('pupil13',$restored->getSearchText());
        $this->assertTrue($restored->hasFilter('formGroup',1));
        $this->assertSame(2,$restored->getPage());
        $this->assertSame(20,$restored->getPageSize());
    }
    public function testEmptyAndPagedReportDataPreserveRowsAndTotals(): void
    {
        $this->assertCount(0,new DataSet([]));
        $rows=[['id'=>4,'name'=>'Student'],['id'=>13,'name'=>'Boundary Boy']];
        $data=(new DataSet($rows))->setResultCount(22,22)->setPagination(2,20);
        $this->assertSame($rows,iterator_to_array($data));
        $this->assertEquals(22,$data->getTotalCount());
        $this->assertEquals(2,$data->getPageCount());
    }
}
