@php($authUser = auth()->user())
<script>
    window.DTS_AUTH_CONTEXT = {
        userId: @json($authUser?->id),
        userName: @json($authUser?->name ?? 'Guest'),
        departmentName: @json($authUser?->department?->name ?? 'No Department Assigned'),
        departmentCode: @json($authUser?->department?->code ?? 'N/A'),
        roleName: @json($authUser?->role?->name ?? 'Guest'),
    };

    const currentUserId = @json($authUser?->id);
    const currentUserName = @json($authUser?->name ?? 'Guest');
    const currentDepartmentName = @json($authUser?->department?->name ?? 'No Department Assigned');
    const currentDepartmentCode = @json($authUser?->department?->code ?? 'N/A');
    const currentUserRole = @json($authUser?->role?->name ?? 'Guest');
</script>
