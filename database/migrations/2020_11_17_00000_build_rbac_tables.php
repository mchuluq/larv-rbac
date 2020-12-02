<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class BuildRbacTables extends Migration{

    public function up(){
        Schema::create('accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('user_id',36);
            $table->string('group_id',64);
            $table->boolean('active')->default(false);
            $table->string('accountable_id',36)->comment('e.g : nim, nik, reg_no');
            $table->string('accountable_type')->comment('e.g : app/models/mahasiswa, app/models/pegawai, app/models/registrasi');
            
            $table->engine = 'InnoDB';
        });
        
        Schema::create('groups', function (Blueprint $table) {
            $table->string('id',64)->primary()->comment('name slug');
            $table->string('name',64);
            $table->text('image')->nullable();
            $table->string('description')->nullable();
                        
            $table->engine = 'InnoDB';
        });
        
        Schema::create('roles', function (Blueprint $table) {
            $table->string('id',64)->primary()->comment('name slug');
            $table->string('name',64);
            $table->string('description')->nullable();
            
            $table->engine = 'InnoDB';
        });
        
        Schema::create('menus', function (Blueprint $table) {
            $table->string('id',64)->primary()->comment('name slug');
            $table->string('route',255)->nullable();
            $table->string('label',64);
            $table->string('html_attr',255)->nullable();
            $table->string('icon',64)->nullable();
            $table->string('parent',64)->nullable();
            $table->string('position',64)->comment('e.g : header, sidebar, footer, etc');
            $table->boolean('is_visible')->default(true);
            $table->boolean('quick_access')->default(true);
            $table->integer('display_order')->default(0);
            $table->string('description')->nullable();
            
            $table->engine = 'InnoDB';
        });

        Schema::create('role_actors', function (Blueprint $table) {
            $table->string('role_id', 64);
            $table->string('group_id', 64)->nullable();
            $table->string('account_id',255)->nullable();

            $table->engine = 'InnoDB';
        });
        
        Schema::create('permissions', function (Blueprint $table) {
            $table->string('menu_id', 64);
            $table->string('group_id', 64)->nullable();
            $table->string('role_id', 64)->nullable();
            $table->string('account_id',255)->nullable();

            $table->engine = 'InnoDB';
        });
        
        Schema::create('remember_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token', 100);
            $table->string('user_id',36);
            $table->timestamps();
            $table->dateTime('expires_at');
            $table->text('user_agent');
            $table->string('ip_address',40);

            $table->unique(['token', 'user_id']);

            $table->engine = 'InnoDB';
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('username',64)->after('email')->unique()->nullable();
            $table->string('phone',20)->after('username')->unique()->nullable();
            $table->text('avatar_url')->after('phone')->nullable();
            $table->boolean('active')->default(true)->after('avatar_url');
            $table->string('account_id',36)->nullable()->after('active')->comment('active account');
            $table->text('otp_secret')->nullable()->after('account_id');
        });
    }

    public function down(){
        Schema::dropIfExists('accounts');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('role_actors');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('remember_tokens');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['avatar_url','account_id','phone','active','username','otp_sceret']);
        });
    }
}
