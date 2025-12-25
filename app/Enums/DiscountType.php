<?php

namespace App\Enums;

enum DiscountType: string
{
    case OrderThresholdFreeShipping = 'order_threshold_free_shipping';
    case QuantityPerSkein = 'quantity_per_skein';
    case Percentage = 'percentage';
    case ManualFreeShipping = 'manual_free_shipping';
    case TimeBoxed = 'time_boxed';
}
