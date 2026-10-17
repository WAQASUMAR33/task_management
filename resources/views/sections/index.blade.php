@extends('layouts.app')

@section('title', 'Sections')
@section('page_title', Auth::user()->isSuperAdmin() ? 'Unit Sections' : 'My Assigned Section')

@section('content')
<div style="margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 16px;">
    <div>
        <h2 style="font-size: 1.5rem; font-weight: 800; letter-spacing: -0.025em; color: var(--mui-on-surface); margin: 0;">
            {{ Auth::user()->isSuperAdmin() ? 'Operational Unit Sections' : 'Section Details & Leadership' }}
        </h2>
        <p style="color: var(--mui-on-surface-variant); font-size: 0.875rem; margin: 4px 0 0 0;">
            {{ Auth::user()->isSuperAdmin() ? 'Manage workspace units, assign section leads, and monitor capacity.' : 'Overview of your assigned section, leadership, and operational team.' }}
        </p>
    </div>

    @if(Auth::user()->isSuperAdmin())
    <button type="button" class="mui-btn mui-btn-gradient" onclick="openCreateSectionModal()">
        <span class="material-symbols-outlined">add_circle</span>
        <span>Create New Section</span>
    </button>
    @endif
</div>

<!-- Sections Grid -->
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
    @forelse($sections as $sec)
    <div class="mui-card" style="display: flex; flex-direction: column; justify-content: space-between; position: relative; border-top: 4px solid var(--mui-primary);">
        <div>
            <!-- Header -->
            <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 12px;">
                <div>
                    <span class="mui-chip" style="font-size: 0.72rem; font-weight: 700; background: var(--mui-primary-light); color: var(--mui-primary); border-color: rgba(79, 70, 229, 0.2); margin-bottom: 6px;">
                        {{ $sec->code }}
                    </span>
                    <h3 style="font-size: 1.2rem; font-weight: 800; color: var(--mui-on-surface); margin: 0; letter-spacing: -0.02em;">
                        {{ $sec->name }}
                    </h3>
                </div>
                <span class="mui-chip {{ $sec->status === 'active' ? 'chip-status-completed' : 'chip-status-todo' }}" style="font-size: 0.72rem;">
                    {{ ucfirst($sec->status) }}
                </span>
            </div>

            <p style="font-size: 0.85rem; color: var(--mui-on-surface-variant); line-height: 1.5; margin: 0 0 16px 0;">
                {{ $sec->description ?: 'No operational description provided.' }}
            </p>

            <!-- Section Admin Information Card -->
            <div style="background: var(--mui-surface-dim); border: 1px solid var(--mui-outline); border-radius: var(--mui-radius-md); padding: 12px 14px; margin-bottom: 16px;">
                <div style="font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: var(--mui-on-surface-variant); margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <span class="material-symbols-outlined" style="font-size: 16px; color: var(--mui-primary);">shield_person</span>
                    <span>Section Lead</span>
                </div>
                @if($sec->admin)
                <div style="display: flex; align-items: center; gap: 10px;">
                    <div class="mui-avatar mui-avatar-sm" style="background-color: {{ $sec->admin->avatar_color }};">
                        {{ strtoupper(substr($sec->admin->name, 0, 1)) }}
                    </div>
                    <div style="min-width: 0;">
                        <div style="font-size: 0.875rem; font-weight: 700; color: var(--mui-on-surface); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $sec->admin->name }}
                        </div>
                        <div style="font-size: 0.75rem; color: var(--mui-on-surface-variant); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                            {{ $sec->admin->email }}
                        </div>
                    </div>
                </div>
                @else
                <div style="font-size: 0.8125rem; color: #b45309; display: flex; align-items: center; gap: 6px; font-weight: 500;">
                    <span class="material-symbols-outlined" style="font-size: 16px;">warning</span>
                    <span>No Section Admin Assigned</span>
                </div>
                @endif
            </div>

            <!-- Stats -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 16px;">
                <div style="padding: 10px 14px; border: 1px solid var(--mui-outline); border-radius: var(--mui-radius-md); background: #ffffff;">
                    <div style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; color: var(--mui-on-surface-variant);">Team Members</div>
                    <div style="font-size: 1.35rem; font-weight: 800; color: var(--mui-primary); margin-top: 2px;">
                        {{ $sec->members_count ?? $sec->members->count() }}
                    </div>
                </div>
                <div style="padding: 10px 14px; border: 1px solid var(--mui-outline); border-radius: var(--mui-radius-md); background: #ffffff;">
                    <div style="font-size: 0.72rem; font-weight: 600; text-transform: uppercase; color: var(--mui-on-surface-variant);">Section Tasks</div>
                    <div style="font-size: 1.35rem; font-weight: 800; color: var(--mui-on-surface); margin-top: 2px;">
                        {{ $sec->tasks_count ?? $sec->tasks->count() }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Actions -->
        <div style="display: flex; align-items: center; justify-content: space-between; border-top: 1px solid var(--mui-outline); padding-top: 14px; margin-top: 8px;">
            <a href="{{ route('sections.show', $sec) }}" class="mui-btn mui-btn-tonal mui-btn-sm">
                <span class="material-symbols-outlined" style="font-size: 16px;">visibility</span>
                <span>View Section</span>
            </a>

            @if(Auth::user()->isSuperAdmin())
            <div style="display: flex; align-items: center; gap: 6px;">
                <button type="button" class="mui-icon-btn mui-icon-btn-sm" title="Edit Section" onclick='openEditSectionModal(@json($sec))'>
                    <span class="material-symbols-outlined" style="font-size: 18px;">edit</span>
                </button>
                <form action="{{ route('sections.destroy', $sec) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this section? All associated tasks will be removed.');" style="margin: 0;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="mui-icon-btn mui-icon-btn-sm" style="color: var(--mui-error);" title="Delete Section">
                        <span class="material-symbols-outlined" style="font-size: 18px;">delete</span>
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
    @empty
    <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #ffffff; border-radius: var(--mui-radius-lg); border: 1.5px dashed var(--mui-outline-strong);">
        <span class="material-symbols-outlined" style="font-size: 48px; color: var(--mui-on-surface-variant); margin-bottom: 12px;">domain_disabled</span>
        <h4 style="font-size: 1.15rem; font-weight: 700; color: var(--mui-on-surface); margin-bottom: 6px;">No Sections Configured</h4>
        <p style="color: var(--mui-on-surface-variant); font-size: 0.875rem;">Create a section to organize teams and deliverables within the unit.</p>
    </div>
    @endforelse
</div>

@if(Auth::user()->isSuperAdmin())
<!-- Create Section Modal -->
<div class="mui-dialog-backdrop" id="createSectionModal">
    <div class="mui-dialog">
        <div class="mui-dialog-header">
            <h3 class="mui-dialog-title">Create New Section</h3>
            <button type="button" class="mui-icon-btn mui-icon-btn-sm" onclick="closeCreateSectionModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form action="{{ route('sections.store') }}" method="POST">
            @csrf
            <input type="hidden" name="unit_id" value="{{ $units->first()->id ?? 1 }}">

            <div class="mui-dialog-body">
                <div class="mui-textfield">
                    <label class="mui-label" for="sec_name">Section Name *</label>
                    <input type="text" name="name" id="sec_name" class="mui-input" required placeholder="e.g. Engineering & Technology">
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="sec_code">Section Code *</label>
                    <input type="text" name="code" id="sec_code" class="mui-input" required placeholder="e.g. SEC-ENG" style="text-transform: uppercase;">
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="sec_admin_id">Section Admin (Lead)</label>
                    <select name="admin_id" id="sec_admin_id" class="mui-select">
                        <option value="">-- Select Section Admin --</option>
                        @foreach($potentialAdmins as $adm)
                        <option value="{{ $adm->id }}">{{ $adm->name }} ({{ $adm->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="sec_description">Description</label>
                    <textarea name="description" id="sec_description" class="mui-textarea" placeholder="Operational responsibilities and scope..."></textarea>
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="sec_status">Operational Status</label>
                    <select name="status" id="sec_status" class="mui-select">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mui-dialog-footer">
                <button type="button" class="mui-btn mui-btn-text" onclick="closeCreateSectionModal()">Cancel</button>
                <button type="submit" class="mui-btn mui-btn-gradient">Create Section</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Section Modal -->
<div class="mui-dialog-backdrop" id="editSectionModal">
    <div class="mui-dialog">
        <div class="mui-dialog-header">
            <h3 class="mui-dialog-title">Edit Section</h3>
            <button type="button" class="mui-icon-btn mui-icon-btn-sm" onclick="closeEditSectionModal()">
                <span class="material-symbols-outlined">close</span>
            </button>
        </div>

        <form id="editSectionForm" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="unit_id" id="edit_unit_id">

            <div class="mui-dialog-body">
                <div class="mui-textfield">
                    <label class="mui-label" for="edit_sec_name">Section Name *</label>
                    <input type="text" name="name" id="edit_sec_name" class="mui-input" required>
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="edit_sec_code">Section Code *</label>
                    <input type="text" name="code" id="edit_sec_code" class="mui-input" required style="text-transform: uppercase;">
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="edit_sec_admin_id">Section Admin (Lead)</label>
                    <select name="admin_id" id="edit_sec_admin_id" class="mui-select">
                        <option value="">-- No Admin Assigned --</option>
                        @foreach($potentialAdmins as $adm)
                        <option value="{{ $adm->id }}">{{ $adm->name }} ({{ $adm->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="edit_sec_description">Description</label>
                    <textarea name="description" id="edit_sec_description" class="mui-textarea"></textarea>
                </div>

                <div class="mui-textfield">
                    <label class="mui-label" for="edit_sec_status">Operational Status</label>
                    <select name="status" id="edit_sec_status" class="mui-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="mui-dialog-footer">
                <button type="button" class="mui-btn mui-btn-text" onclick="closeEditSectionModal()">Cancel</button>
                <button type="submit" class="mui-btn mui-btn-gradient">Update Section</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openCreateSectionModal() {
        openDialog('createSectionModal');
    }
    function closeCreateSectionModal() {
        closeDialog('createSectionModal');
    }
    function openEditSectionModal(sec) {
        document.getElementById('editSectionForm').action = '/sections/' + sec.id;
        document.getElementById('edit_unit_id').value = sec.unit_id;
        document.getElementById('edit_sec_name').value = sec.name;
        document.getElementById('edit_sec_code').value = sec.code;
        document.getElementById('edit_sec_admin_id').value = sec.admin_id || '';
        document.getElementById('edit_sec_description').value = sec.description || '';
        document.getElementById('edit_sec_status').value = sec.status;
        openDialog('editSectionModal');
    }
    function closeEditSectionModal() {
        closeDialog('editSectionModal');
    }
</script>
@endif
@endsection
