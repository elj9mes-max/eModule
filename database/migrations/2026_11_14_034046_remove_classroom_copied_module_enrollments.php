<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Classroom access is now resolved live from classroom membership (Module::visibleToStudent),
     * so module_enrollments only records direct module-code joins. Remove the rows the old
     * behaviour copied in from classrooms:
     *
     *  1. Redundant copies: the student is still in a classroom that has the module.
     *  2. Stale copies: the module belongs to a classroom and the student is in a classroom,
     *     but none of the student's classrooms has the module (left over from removed students/modules).
     *
     * Rows for students who are in no classroom are kept, since those came from direct module-code joins.
     */
    public function up(): void
    {
        DB::table('module_enrollments')
            ->whereExists(fn (Builder $query) => $query
                ->selectRaw('1')
                ->from('classroom_students')
                ->join('classroom_modules', 'classroom_modules.classroom_id', '=', 'classroom_students.classroom_id')
                ->whereColumn('classroom_students.user_id', 'module_enrollments.user_id')
                ->whereColumn('classroom_modules.module_id', 'module_enrollments.module_id'))
            ->delete();

        DB::table('module_enrollments')
            ->whereExists(fn (Builder $query) => $query
                ->selectRaw('1')
                ->from('classroom_modules')
                ->whereColumn('classroom_modules.module_id', 'module_enrollments.module_id'))
            ->whereExists(fn (Builder $query) => $query
                ->selectRaw('1')
                ->from('classroom_students')
                ->whereColumn('classroom_students.user_id', 'module_enrollments.user_id'))
            ->delete();
    }

    /**
     * The removed rows were derived copies and cannot be restored meaningfully.
     */
    public function down(): void
    {
        //
    }
};
