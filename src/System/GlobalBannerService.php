<?php
/**
 * @copyright (c) 2016 Quicken Loans Inc.
 *
 * For full license information, please view the LICENSE distributed with this source code.
 */

namespace Hal\UI\System;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Hal\Core\Entity\System\SystemSetting;
use Hal\Core\Utility\CachingTrait;
use QL\MCP\Common\Clock;
use QL\Panthor\Utility\JSON;

class GlobalBannerService
{
    use CachingTrait;

    private const CACHE_BANNER = 'setting.banner';
    private const CACHE_NOTIFICATION = 'setting.update_notification';
    private const CACHE_NOTIFICATION_OFF = 'setting.update_notification_cached';

    public const NAME_BANNER = 'global.banner';
    public const NAME_NOTIFICATION = 'global.update_notification';

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var EntityRepository|null
     */
    private $settingRepo;

    /**
     * @var Clock
     */
    private $clock;

    /**
     * @var JSON
     */
    private $json;

    /**
     * @param EntityManagerInterface $em
     * @param Clock $clock
     * @param JSON $json
     */
    public function __construct(EntityManagerInterface $em, Clock $clock, JSON $json)
    {
        $this->em = $em;

        $this->clock = $clock;
        $this->json = $json;
    }

    /**
     * Fetch the global message, if it is set.
     *
     * @return string
     */
    public function fetchBanner(): string
    {
        if ($result = $this->getFromCache(self::CACHE_BANNER)) {
            $decoded = $this->decodeMessage($result);
            return $this->getBannerIfNotExpired($decoded);
        }

        $decoded = $this->loadBannerDetails();

        $encoded = $this->json->encode($decoded);
        $this->setToCache(self::CACHE_BANNER, $encoded);

        return $this->getBannerIfNotExpired($decoded);
    }

    /**
     * @return bool
     */
    public function isUpdateNotificationEnabled(): bool
    {
        if ($result = $this->getFromCache(self::CACHE_NOTIFICATION)) {
            return true;
        }

        if ($result = $this->getFromCache(self::CACHE_NOTIFICATION_OFF)) {
            return false;
        }

        $setting = $this->settingRepo()->findOneBy(['name' => self::NAME_NOTIFICATION]);
        if ($setting instanceof SystemSetting) {
            return ($setting->value() === '1');
        }

        return false;
    }

    /**
     * @return array
     */
    public function loadBannerDetails()
    {
        $message = $this->settingRepo()->findOneBy(['name' => self::NAME_BANNER]);
        if (!$message instanceof SystemSetting) {
            return $this->messagePayload('', 0);
        }

        $decoded = $this->decodeMessage($message->value());
        return $decoded;
    }

    /**
     * Persist the global message
     *
     * @param string $message
     * @param int $ttl
     *
     * @return void
     */
    public function saveBanner($message, int $ttl = 0)
    {

        $setting = $this->settingRepo()->findOneBy(['name' => self::NAME_BANNER]);
        if (!$setting instanceof SystemSetting) {
            $setting = new SystemSetting;
        }

        $payload = $this->messagePayload($message, $ttl);
        $encoded = $this->json->encode($payload);

        $setting
            ->withName(self::NAME_BANNER)
            ->withValue($encoded);

        $this->em->persist($setting);
        $this->em->flush();

        $this->setToCache(self::CACHE_BANNER, $encoded);
    }

    /**
     * Clear the global message
     *
     * @return void
     */
    public function clearBanner()
    {
        $setting = $this->settingRepo()->findOneBy(['name' => self::NAME_BANNER]);
        if ($setting instanceof SystemSetting) {
            $this->em->remove($setting);
            $this->em->flush();
        }

        $payload = $this->messagePayload('', 0);
        $this->setToCache(self::CACHE_BANNER, $payload);
    }

    /**
     * @return void
     */
    public function enableUpdateNotification()
    {
        $setting = $this->settingRepo()->findOneBy(['name' => self::NAME_NOTIFICATION]);
        if (!$setting instanceof SystemSetting) {
            $setting = new SystemSetting;
        }

        $setting
            ->withName(self::NAME_NOTIFICATION)
            ->withValue('1');

        $this->em->persist($setting);
        $this->em->flush();

        $this->setToCache(self::CACHE_NOTIFICATION, $setting->value());
        $this->setToCache(self::CACHE_NOTIFICATION_OFF, null);
    }

    /**
     * @return void
     */
    public function disableUpdateNotification()
    {
        if ($setting = $this->settingRepo()->findOneBy(['name' => self::NAME_NOTIFICATION])) {
            $this->em->remove($setting);
            $this->em->flush();
        }

        $this->setToCache(self::CACHE_NOTIFICATION, null);
        $this->setToCache(self::CACHE_NOTIFICATION_OFF, '1');
    }


    /**
     * @param mixed $payload
     *
     * @return string
     */
    private function getBannerIfNotExpired($payload)
    {
        $message = $payload['message'] ?? '';
        $isExpired = isset($payload['is_expired']) ? $payload['is_expired'] : true;

        return $isExpired ? '' : $message;
    }

    /**
     * @param string $value
     *
     * @return array
     */
    private function decodeMessage($value)
    {
        $decoded = $this->json->decode($value);

        $message = $decoded['message'] ?? '';
        $expiry = $decoded['expiry'] ?? '';
        $ttl = $decoded['ttl'] ?? 0;

        $expired = false;
        if ($expiry) {
            $now = $this->clock->read();
            $expiry = $this->clock->fromString($expiry);

            $expired = ($expiry->compare($now) !== 1);
        }

        return [
            'message' => $message,
            'expiry' => $expiry,
            'ttl' => $ttl,

            'is_expired' => $expired
        ];
    }

    /**
     * @param string $message
     * @param int $ttl
     *
     * @return array
     */
    private function messagePayload($message, int $ttl = 0)
    {
        $expiry = '';
        if ($ttl > 0) {
            $now = $this->clock->read();
            $expiry = $now->modify("+${ttl} seconds");
        }

        return [
            'message' => $message,
            'expiry' => $expiry,
            'ttl' => $ttl
        ];
    }

    /**
     * @return EntityRepository
     */
    private function settingRepo()
    {
        if (!$this->settingRepo) {
            $this->settingRepo = $this->em->getRepository(SystemSetting::class);
        }

        return $this->settingRepo;
    }
}
