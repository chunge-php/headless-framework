<?php

function getBaseStatusName($key)
{
    $data = [
        0 => trans('forbidden'),
        1 => trans('start_using'),
    ];
    return $data[$key] ?? '';
}

function getTemplatTypeName($key)
{
    $data = [
        0 => trans('sms_name'),
        1 => trans('mms_name'),
        2 => trans('email_name'),
    ];
    return $data[$key] ?? '';
}

function getStatusNameAttribute($key)
{
    $status = [
        0 => trans('not_start'),
        1 => trans('succeed'),
        2 => trans('running'),
        3 => trans('fail'),
        4=> trans('warning'),
        5=> trans('retry'),
    ];
    return $status[$key] ?? '';
}
function getSubscribeTypeName($key)
{
    //类型0立即发送1预约发送2提醒任务3系统任务
    $data = [
        0 => trans('promptly'),
        1 => trans('subscribe'),
        2 => trans('remind'),
        3 => trans('system')
    ];
    return $data[$key] ?? '';
}

function getBillTypeNameAttribute($key)
{
    $status = [
        0 => trans('bill_type_consume'),
        1 => trans('bill_type_recharge'),
    ];
    return $status[$key] ?? '';
}

function getBaseState($key)
{
    $data = [
        0 => trans('no'),
        1 => trans('yes'),
    ];
    return $data[$key] ?? trans('no');
}