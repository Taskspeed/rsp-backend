<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPlacementAccessFieldsToUserRspControlTable extends Migration
{
    public function up()
    {
        Schema::table('user_rsp_control', function (Blueprint $table) {
            $table->boolean('viewPlacementAccess')->default(false)->after('reportAdvanceAppointmentAccess');
            $table->boolean('modifyPlacementAccess')->default(false)->after('viewPlacementAccess');
            $table->boolean('reportPlacementAccess')->default(false)->after('modifyPlacementAccess');
        });
    }

    public function down()
    {
        Schema::table('user_rsp_control', function (Blueprint $table) {
            $table->dropColumn([
                'viewPlacementAccess',
                'modifyPlacementAccess',
                'reportPlacementAccess'
            ]);
        });
    }
}