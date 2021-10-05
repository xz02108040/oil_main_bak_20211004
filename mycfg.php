<?php

return [
    /**
     * 基本參數
     */
    'sys_kind'      => 'A', //系統辨識碼

    'file_upload_limit' => '2000000', //上傳檔案上限
    'file_upload_limit_name' => '20MB', //上傳檔案上限
    /**
     * 加解密
     */
    'randkey'       => 'Uv51rEDP1G6j1YPx', //
    'randiv'        => '2019051043428932', //
    /**
     * 2019-12-18 烏材林介接金鑰
     */
    'app_key_20191218'      => 'kG48vWMLCtbXTLmAwBMTgOOvnSFpYo1c', //
    /**
     * 檔案位置
     */
    'user_head_path'        => '/BCUST/', //個人圖片
    'car_head_path'         => '/CAR/',   //車輛圖片
    'course_path'           => '/COURSE/', //課程用檔案
    'license_path'          => '/SUPPLY/LICENSE/', //供應商成員之證照證明
    'supply_car_apth'       => '/SUPPLY/CAR/', //供應商成員之車輛證明

    'car_supply_path'       => '/CAR/SUPPLY/', //供應商成員之車輛證明
    'car_member_path'       => '/CAR/MEMBER/', //職員成員之車輛證明


    'door_inout_path'       => '/DOOR/INOUT/', //職員成員之車輛證明

    'api_receive_path'      => '/API/RECEIVE/', //API_接收
    'api_reply_path'        => '/API/REPLY/',   //API_回覆

    'app_receive_path'      => '/API/APP_RECEIVE/', //API_接收
    'app_reply_path'        => '/API/APP_REPLY/',   //API_回覆

    'permit_path'           => '/PERMIT/WORK/',   //工作許可證
    'permit_check_path'     => '/PERMIT/CHECK/',   //工作許可證
    'permit_checkfile_path' => '/PERMIT/CHECKKIND/',   //工作許可證

    'api_receive_path2'     => '/API/CARD_RECEIVE/', //印卡API_接收
    'api_reply_path2'       => '/API/CARD_REPLY/',   //印卡API_回覆

    'api_receive_path3'     => '/API/REPT_RECEIVE/', //報表API_接收
    'api_reply_path3'       => '/API/REPT_REPLY/',   //報表API_回覆

    'rp_license_path'       => '/SUPPLY/RP/LICENSE/', //供應商成員之證照證明
];
