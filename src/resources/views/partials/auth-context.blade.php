@php($authUser = auth()->user())
<script>
    window.DTS_AUTH_CONTEXT = {
        userId: @json($authUser?->id),
        departmentId: {{ $authUser?->department_id ?? 'null' }},
        userName: @json($authUser?->name),
        departmentName: @json($authUser?->department?->name),
        departmentCode: @json($authUser?->department?->code),
        roleName: @json($authUser?->role?->name),
    };

    const currentUserId = @json($authUser?->id);
    const currentUserName = @json($authUser?->name);
    const currentDepartmentName = @json($authUser?->department?->name);
    const currentDepartmentCode = @json($authUser?->department?->code);
    const currentUserRole = @json($authUser?->role?->name);
</script>
