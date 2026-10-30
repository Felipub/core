<?php
namespace Regression\Unit;
use PHPUnit\Framework\TestCase;
use Gibbon\FileUploader;
use Gibbon\Contracts\Database\Connection;
use Gibbon\Session;

final class UploadFailureTest extends TestCase
{
    /** @dataProvider uploadErrors */
    public function testUploadErrorsCannotBecomeSuccessfulFilePaths(int $error): void
    {
        $uploader=new FileUploader($this->createMock(Connection::class),$this->createMock(Session::class));
        $this->assertFalse($uploader->uploadFromPost(['name'=>'synthetic.png','tmp_name'=>'','error'=>$error]));
    }
    public function uploadErrors(): array
    {
        return [[UPLOAD_ERR_INI_SIZE],[UPLOAD_ERR_FORM_SIZE],[UPLOAD_ERR_PARTIAL],[UPLOAD_ERR_NO_FILE],[UPLOAD_ERR_NO_TMP_DIR],[UPLOAD_ERR_CANT_WRITE],[UPLOAD_ERR_EXTENSION]];
    }
}
