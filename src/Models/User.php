<?php namespace Mchuluq\Larv\Rbac\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;

use Mchuluq\Larv\Rbac\Traits\HasRbac;
use Mchuluq\Larv\Rbac\Traits\RevokesAccess;

/**
 * Base model user untuk aplikasi yang memakai larv-rbac.
 *
 * Status aktif / nonaktif user diwakili SoftDeletes, bukan kolom boolean
 * tersendiri. Alasannya: global scope SoftDeletingScope menutup seluruh jalur
 * pencarian user sekaligus - login username/password, pemulihan session,
 * remember token, password grant Passport, sampai lookup by email pada login
 * sosial - tanpa perlu menambah filter di tiap jalur. Menonaktifkan user
 * cukup delete(), mengaktifkan kembali cukup restore().
 *
 * RevokesAccess menempel pada lifecycle tersebut: delete() memutus session,
 * perangkat, dan token OAuth yang sudah terbit; restore() membersihkan cache
 * rbac supaya permission dibangun ulang.
 *
 * Aplikasi mewarisi model ini dan mengisi sendiri $table, $fillable, $casts,
 * relasi profil, dan trait tambahan lain yang dibutuhkan.
 */
abstract class User extends Authenticatable {

    use SoftDeletes, HasRbac, RevokesAccess;

}
