<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Saltedpasswords\Tests\UnitDeprecated\Salt;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Saltedpasswords\Salt\SaltFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class SaltFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function abstractComposedSaltBase64EncodeReturnsProperLength()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords'] = [
            'BE' => [
                'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt::class,
            ],
            'FE' => [
                'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt::class,
            ],
        ];

        // set up an instance that extends AbstractComposedSalt first
        $saltPbkdf2 = '$pbkdf2-sha256$6400$0ZrzXitFSGltTQnBWOsdAw$Y11AchqV4b0sUisdZd0Xr97KWoymNE0LNNrnEgY4H9M';
        $objectInstance = SaltFactory::getSaltingInstance($saltPbkdf2);

        // 3 Bytes should result in a 6 char length base64 encoded string
        // used for MD5 and PHPass salted hashing
        $byteLength = 3;
        $reqLengthBase64 = (int)ceil($byteLength * 8 / 6);
        $randomBytes = (new Random())->generateRandomBytes($byteLength);
        $this->assertTrue(strlen($objectInstance->base64Encode($randomBytes, $byteLength)) == $reqLengthBase64);
        // 16 Bytes should result in a 22 char length base64 encoded string
        // used for Blowfish salted hashing
        $byteLength = 16;
        $reqLengthBase64 = (int)ceil($byteLength * 8 / 6);
        $randomBytes = (new Random())->generateRandomBytes($byteLength);
        $this->assertTrue(strlen($objectInstance->base64Encode($randomBytes, $byteLength)) == $reqLengthBase64);
    }

    /**
     * @test
     */
    public function objectInstanceForMD5Salts()
    {
        $saltMD5 = '$1$rasmusle$rISCgZzpwk3UhDidwXvin0';
        $objectInstance = SaltFactory::getSaltingInstance($saltMD5);
        $this->assertTrue(get_class($objectInstance) == \TYPO3\CMS\Saltedpasswords\Salt\Md5Salt::class || is_subclass_of($objectInstance, \TYPO3\CMS\Saltedpasswords\Salt\Md5Salt::class));
        $this->assertInstanceOf(\TYPO3\CMS\Saltedpasswords\Salt\AbstractComposedSalt::class, $objectInstance);
    }

    /**
     * @test
     */
    public function objectInstanceForBlowfishSalts()
    {
        $saltBlowfish = '$2a$07$abcdefghijklmnopqrstuuIdQV69PAxWYTgmnoGpe0Sk47GNS/9ZW';
        $objectInstance = SaltFactory::getSaltingInstance($saltBlowfish);
        $this->assertTrue(get_class($objectInstance) == \TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt::class || is_subclass_of($objectInstance, \TYPO3\CMS\Saltedpasswords\Salt\BlowfishSalt::class));
        $this->assertInstanceOf(\TYPO3\CMS\Saltedpasswords\Salt\AbstractComposedSalt::class, $objectInstance);
    }

    /**
     * @test
     */
    public function objectInstanceForPhpassSalts()
    {
        $saltPhpass = '$P$CWF13LlG/0UcAQFUjnnS4LOqyRW43c.';
        $objectInstance = SaltFactory::getSaltingInstance($saltPhpass);
        $this->assertTrue(get_class($objectInstance) == \TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::class || is_subclass_of($objectInstance, \TYPO3\CMS\Saltedpasswords\Salt\PhpassSalt::class));
        $this->assertInstanceOf(\TYPO3\CMS\Saltedpasswords\Salt\AbstractComposedSalt::class, $objectInstance);
    }

    /**
     * @test
     */
    public function objectInstanceForPbkdf2Salts()
    {
        $saltPbkdf2 = '$pbkdf2-sha256$6400$0ZrzXitFSGltTQnBWOsdAw$Y11AchqV4b0sUisdZd0Xr97KWoymNE0LNNrnEgY4H9M';
        $objectInstance = SaltFactory::getSaltingInstance($saltPbkdf2);
        $this->assertTrue(get_class($objectInstance) == \TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt::class || is_subclass_of($objectInstance, \TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt::class));
        $this->assertInstanceOf(\TYPO3\CMS\Saltedpasswords\Salt\AbstractComposedSalt::class, $objectInstance);
    }

    /**
     * @test
     */
    public function objectInstanceForPhpPasswordHashBcryptSalts()
    {
        $saltBcrypt = '$2y$12$Tz.al0seuEgRt61u0bzqAOWu67PgG2ThG25oATJJ0oS5KLCPCgBOe';
        $objectInstance = SaltFactory::getSaltingInstance($saltBcrypt);
        $this->assertInstanceOf(\TYPO3\CMS\Saltedpasswords\Salt\BcryptSalt::class, $objectInstance);
    }

    /**
     * @test
     */
    public function objectInstanceForPhpPasswordHashArgon2iSalts()
    {
        $saltArgon2i = '$argon2i$v=19$m=8,t=1,p=1$djZiNkdEa3lOZm1SSmZsdQ$9iiRjpLZAT7kfHwS1xU9cqSU7+nXy275qpB/eKjI1ig';
        $objectInstance = SaltFactory::getSaltingInstance($saltArgon2i);
        $this->assertInstanceOf(\TYPO3\CMS\Saltedpasswords\Salt\Argon2iSalt::class, $objectInstance);
    }

    /**
     * @test
     */
    public function resettingFactoryInstanceSucceeds()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['saltedpasswords'] = [
            'BE' => [
                'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt::class,
            ],
            'FE' => [
                'saltedPWHashingMethod' => \TYPO3\CMS\Saltedpasswords\Salt\Pbkdf2Salt::class,
            ],
        ];

        $defaultClassNameToUse = \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::getDefaultSaltingHashingMethod();
        if ($defaultClassNameToUse == \TYPO3\CMS\Saltedpasswords\Salt\Md5Salt::class) {
            $saltedPW = '$P$CWF13LlG/0UcAQFUjnnS4LOqyRW43c.';
        } else {
            $saltedPW = '$1$rasmusle$rISCgZzpwk3UhDidwXvin0';
        }
        $objectInstance = SaltFactory::getSaltingInstance($saltedPW);
        // resetting
        $objectInstance = SaltFactory::getSaltingInstance(null);
        $this->assertTrue(get_class($objectInstance) == $defaultClassNameToUse || is_subclass_of($objectInstance, $defaultClassNameToUse));
    }
}
