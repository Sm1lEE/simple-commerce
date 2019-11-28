<?php

namespace Damcclean\Commerce\Models;

class Coupon extends BaseModel
{
    public $name = 'Coupon';
    public $slug = 'coupon';
    public $route = 'coupons';
    public $primaryColumn = 'title';

    public function isValid(string $coupon)
    {
        return $this->get($coupon)['enabled'];
    }
}