<?php

namespace Moynzzz\TeslaApi\Enum;

enum ApiRegion: string {
    case na = 'na';
    case eu = 'eu';
    case cn = 'cn';

    public function getApiUrl(): string {
        return match($this) {
            self::na => 'https://fleet-api.prd.na.vn.cloud.tesla.com',
            self::eu => 'https://fleet-api.prd.eu.vn.cloud.tesla.com',
            self::cn => 'https://fleet-api.prd.cn.vn.cloud.tesla.cn',
        };
    }
}
