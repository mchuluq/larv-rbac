<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class ReplaceUsersTable extends Migration{

    public function up(){
        Schema::create('rbac_accounts', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('user_id',36);
            $table->string('group_slug',64);
            $table->string('accountable_id',36)->comment('e.g : nim, nik, reg_no');
            $table->string('accountable_type')->comment('e.g : app/models/mahasiswa, app/models/pegawai, app/models/registrasi');
            
            $table->primary('id');
            $table->engine = 'InnoDB';
        });
        
        Schema::create('rbac_groups', function (Blueprint $table) {
            $table->string('slug',64);
            $table->string('name',64);
            $table->string('description')->nullable();
            
            $table->primary('slug');
            $table->engine = 'InnoDB';
        });
        
        Schema::create('rbac_roles', function (Blueprint $table) {
            $table->string('slug',64);
            $table->string('name',64);
            $table->string('description')->nullable();
            
            $table->primary('slug');
            $table->engine = 'InnoDB';
        });
        
        Schema::create('rbac_menus', function (Blueprint $table) {
            $table->string('slug',64);
            $table->string('route',255)->nullable();
            $table->string('label',64);
            $table->string('html_attr',255)->nullable();
            $table->string('parameter',255)->comment('additional param, json object or just string')->nullable();
            $table->string('icon',64)->nullable();
            $table->string('parent',64)->nullable();
            $table->string('position',64)->comment('e.g : header, sidebar, footer, etc');
            $table->boolean('is_visible')->default(true);
            $table->boolean('quick_access')->default(true);
            $table->integer('display_order')->default(0);
            $table->string('description')->nullable();
            
            $table->primary('slug');
            $table->engine = 'InnoDB';
        });

        Schema::create('rbac_role_actors', function (Blueprint $table) {
            $table->string('role_slug', 64);
            $table->string('group_slug', 64)->nullable();
            $table->string('account_id',255)->nullable();

            $table->engine = 'InnoDB';
        });
        
        Schema::create('rbac_permissions', function (Blueprint $table) {
            $table->string('menu_slug', 64);
            $table->string('group_slug', 64)->nullable();
            $table->string('role_slug', 64)->nullable();
            $table->string('account_id',255)->nullable();

            $table->engine = 'InnoDB';
        });
        
        Schema::create('rbac_remember_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('token', 100);
            $table->string('user_id',36);
            $table->timestamps();
            $table->dateTime('expires_at');

            $table->unique(['token', 'user_id']);

            $table->engine = 'InnoDB';
        });
    }

    public function down(){
        Schema::dropIfExists('rbac_accounts');
        Schema::dropIfExists('rbac_groups');
        Schema::dropIfExists('rbac_roles');
        Schema::dropIfExists('rbac_menus');
        Schema::dropIfExists('rbac_role_actors');
        Schema::dropIfExists('rbac_permissions');
    }
}
