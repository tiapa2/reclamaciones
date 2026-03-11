<?php

namespace App\Support;

trait DoctorScope
{
    protected function scopedDoctorId(): ?int
    {
        $u = auth()->user();
        if ($u && $u->hasRole('doctor')) {
            return optional($u->doctor)->id;
        }
        return null; // admin y analista ven todo
    }
}
