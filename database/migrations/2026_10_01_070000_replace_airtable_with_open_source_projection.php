<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if(!Schema::hasTable('galika_projection_records')){
            Schema::create('galika_projection_records',function(Blueprint $table){
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('correlation_id',64);
                $table->string('event_type')->index();
                $table->json('payload');
                $table->string('mirror_state')->default('LOCAL_ONLY')->index();
                $table->timestampTz('mirrored_at')->nullable();
                $table->text('last_mirror_error')->nullable();
                $table->timestampsTz();
                $table->unique(['user_id','correlation_id']);
            });
        }

        if(Schema::hasTable('galika_outbox')){
            DB::table('galika_outbox')
                ->where('destination','airtable')
                ->update(['destination'=>'baserow','updated_at'=>now()]);
        }

        if(Schema::hasTable('galika_connections')){
            foreach(DB::table('galika_connections')->where('provider','airtable')->get() as $connection){
                $provider='airtable_legacy';
                if(DB::table('galika_connections')
                    ->where('user_id',$connection->user_id)
                    ->where('provider',$provider)
                    ->where('id','<>',$connection->id)
                    ->exists()){
                    $provider='airtable_legacy_'.$connection->id;
                }
                DB::table('galika_connections')
                    ->where('id',$connection->id)
                    ->update(['provider'=>$provider,'updated_at'=>now()]);
            }
        }
    }

    public function down(): void
    {
        if(Schema::hasTable('galika_outbox')){
            DB::table('galika_outbox')
                ->where('destination','baserow')
                ->update(['destination'=>'airtable','updated_at'=>now()]);
        }

        if(Schema::hasTable('galika_connections')){
            foreach(DB::table('galika_connections')->where('provider','like','airtable_legacy%')->get() as $connection){
                if(!DB::table('galika_connections')
                    ->where('user_id',$connection->user_id)
                    ->where('provider','airtable')
                    ->exists()){
                    DB::table('galika_connections')
                        ->where('id',$connection->id)
                        ->update(['provider'=>'airtable','updated_at'=>now()]);
                }
            }
        }

        Schema::dropIfExists('galika_projection_records');
    }
};
