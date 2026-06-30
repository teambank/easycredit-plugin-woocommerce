<?php

declare(strict_types=1);
/*
 * (c) NETZKOLLEKTIV GmbH <kontakt@netzkollektiv.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Netzkollektiv\EasyCredit\Api;

use Teambank\EasyCreditApiV3\Integration\ValidationException;

class AddressValidator extends \Teambank\EasyCreditApiV3\Integration\Util\AddressValidator
{
    public const COMPANY_NOT_ALLOWED_MESSAGE = 'Die Zahlung mit easyCredit ist nur für Privatpersonen möglich.';

    public function validate($request)
    {
        $company = $request->getCustomer()->getCompanyName();
        if (trim((string) $company) !== '') {
            throw new ValidationException(self::COMPANY_NOT_ALLOWED_MESSAGE);
        }

        parent::validate($request);
    }
}
