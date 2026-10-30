<?php
namespace Regression\Unit;
use PHPUnit\Framework\TestCase;
use Gibbon\Database\Result;
use Gibbon\Domain\System\CustomFieldGateway;
use Gibbon\FileUploader;
use Gibbon\Forms\CustomFieldHandler;
use Gibbon\Services\Format;

final class CustomFieldsTest extends TestCase
{
    private $post;
    protected function setUp(): void { $this->post=$_POST; }
    protected function tearDown(): void { $_POST=$this->post; }
    private function handler(array $fields): CustomFieldHandler
    {
        $result=$this->createMock(Result::class);
        $result->method('fetchAll')->willReturn($fields);
        $gateway=$this->getMockBuilder(CustomFieldGateway::class)->disableOriginalConstructor()->getMock();
        $gateway->method('selectCustomFields')->willReturn($result);
        $uploader=$this->getMockBuilder(FileUploader::class)->disableOriginalConstructor()->getMock();
        return new CustomFieldHandler($gateway,$uploader);
    }
    public function testDateCheckboxesAndTextBecomeExpectedStoredValues(): void
    {
        Format::setup(['dateFormatPHP'=>'d/m/Y']);
        $handler=$this->handler([
            ['gibbonCustomFieldID'=>'0090','type'=>'date','required'=>'N'],
            ['gibbonCustomFieldID'=>'0091','type'=>'checkboxes','required'=>'N'],
            ['gibbonCustomFieldID'=>'0092','type'=>'text','required'=>'N'],
        ]);
        $_POST=['custom0090'=>'29/02/2024','custom0091'=>['One','Two'],'custom0092'=>'Synthetic value'];
        $failed=false;
        $this->assertSame(['0090'=>'2024-02-29','0091'=>'One,Two','0092'=>'Synthetic value'],json_decode($handler->getFieldDataFromPOST('User',[],$failed),true));
        $this->assertFalse($failed);
    }
    public function testParentPrefixKeepsFamilyValuesSeparateFromStudent(): void
    {
        $handler=$this->handler([['gibbonCustomFieldID'=>'0092','type'=>'text','required'=>'N']]);
        $_POST=['custom0092'=>'Student value','parent1custom0092'=>'Adult value'];
        $failed=false;
        $this->assertSame(['0092'=>'Adult value'],json_decode($handler->getFieldDataFromPOST('User',['prefix'=>'parent1custom'],$failed),true));
    }
    public function testMissingRequiredCustomFieldSignalsValidationFailure(): void
    {
        $handler=$this->handler([['gibbonCustomFieldID'=>'0092','type'=>'text','required'=>'Y']]);
        $_POST=[];
        $failed=false;
        $handler->getFieldDataFromPOST('User',[],$failed);
        $this->assertTrue((bool)$failed,'An absent required field must fail validation');
    }
}
