<?php

namespace Firesphere\BootstrapMFA\Models;

use Firesphere\BootstrapMFA\Generators\CodeGenerator;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DataList;
use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Class BackupCode
 *
 * @property string $Code
 * @property boolean $Used
 * @property int $MemberID
 * @method \SilverStripe\Security\Member Member()
 */
class BackupCode extends DataObject
{
    private static $table_name = 'BackupCode';

    private static $db = [
        'Code' => 'Varchar(255)',
        'Used' => 'Boolean(false)'
    ];

    private static $has_one = [
        'Member' => Member::class
    ];

    private static $indexes = [
        'Code' => [
            'type'    => 'unique',
            'columns' => ['MemberID', 'Code'],
        ],
    ];

    /**
     * @param $member
     */
    protected static function sendWarningEmail($member)
    {
        /** @var Email $mail */
        $mail = Email::create();
        $mail->setTo($member->Email);
        $mail->setFrom(Config::inst()->get(Email::class, 'admin_email'));
        $mail->setSubject(_t(static::class . '.REGENERATIONMAIL', 'Your backup tokens need to be regenerated'));
        $mail->setBody(_t(static::class . '.REGENERATIONREQUIRED',
            sprintf('<p>Your backup codes for multi factor authentication have been requested to regenerate by someone that is not you. 
                    Please visit the <a href="%s/%s">website to regenerate your backupcodes</p>',
                Director::absoluteURL(), Security::config()->get('lost_password_url'))));
    }

    /**
     * @param $member
     * @return string
     * @throws \SilverStripe\ORM\ValidationException
     */
    private static function createCode($member)
    {
        $code = static::create();
        $code->MemberID = $member->ID;
        $token = $code->Code;
        $code->write();
        $code->destroy();

        return $token;
    }

    /**
     * @return mixed
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    protected function generateToken()
    {
        $config = Config::inst()->get(CodeGenerator::class);
        $generator = Injector::inst()->get(CodeGenerator::class)
            ->setLength($config['length']);
        switch ($config['type']) {
            case 'mixed':
                $generator->alphanumeric();
                break;
            case 'numeric':
                $generator->numbersonly();
                break;
            case 'characters':
                $generator->charactersonly();
                break;
            default:
                $generator->numbersonly();
        }
        switch ($config['case']) {
            case 'upper':
                $generator->uppercase();
                break;
            case 'lower':
                $generator->lowercase();
                break;
            case 'mixed':
                $generator->mixedcase();
                break;
            default:
                $generator->mixedcase();
        }

        return $generator->generate();
    }

    /**
     * @return DataObject
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public function populateDefaults()
    {
        $this->Code = $this->generateToken();

        return parent::populateDefaults();
    }

    /**
     * @param Member $member
     * @return DataList|static[]
     */
    public static function getValidTokensForMember($member)
    {
        return static::get()->filter(
            [
                'Used'     => false,
                'MemberID' => $member->ID
            ]
        );
    }

    /**
     * @param Member $member
     * @throws \SilverStripe\ORM\ValidationException
     */
    public static function generateTokensForMember($member)
    {
        if (Security::getCurrentUser() && (int)Security::getCurrentUser()->ID !== $member->ID) {
            self::sendWarningEmail($member);
        } else {
            $message = _t(static::class . 'SESSIONMESSAGE_START',
                '<p>Here are your tokens, please store them securily. ' .
                'They are stored encrypted and can not be recovered, only reset.</p><p>');
            $session = Controller::curr()->getRequest()->getSession();
            $limit = static::config()->get('token_limit');
            for ($i = 0; $i < $limit; ++$i) {
                $token = self::createCode($member);
                $message .= sprintf('%s<br />', $token);
            }
            $message .= '</p>';
            $session->set('tokens', $message);
        }
    }

    /**
     * @throws \SilverStripe\Security\PasswordEncryptor_NotFoundException
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        // Encrypt a new temporary key before writing to the database
        if (!$this->Used) {
            $member = $this->Member();
            $this->Code = $member->encryptWithUserSettings($this->Code);
        }
    }

    /**
     * @return $this
     * @throws \SilverStripe\ORM\ValidationException
     */
    public function expire()
    {
        $this->Used = true;
        $this->write();

        return $this;
    }

    /**
     * @param null $member
     * @return bool
     */
    public function canEdit($member = null)
    {
        return false;
    }
}