<?php

namespace App;

//use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use SMartins\PassportMultiauth\HasMultiAuthApiTokens;
use Illuminate\Notifications\Notifiable;

class Employee extends Authenticatable
{
    use Notifiable, HasMultiAuthApiTokens;

    protected $guard = 'staff';

    protected $guarded = ['id'];

    
    public function type()
    {
        
        return $this->belongsTo('App\StaffType', 'staff_type_id');

    }

    public function staffDepartment()
    {
        
        return $this->belongsTo('App\Department', 'department_id');

    }

    public function staff()
    {
        
        return $this->hasOne('App\Department', 'head_of_department');

    }

    public function attendanceLogs()
    {
        return $this->hasMany('App\StaffAttendance');
    }

    public function sender()
    {
        return $this->hasMany('App\Message', 'sender_id');
    }

    public function recipient()
    {
        return $this->hasMany('App\Message', 'recipient_id');
    }





}
