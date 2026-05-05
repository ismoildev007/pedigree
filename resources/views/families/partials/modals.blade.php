<!-- Add Person Modal -->
<div class="modal fade" id="addPersonModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('people.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="family_id" value="{{ $family->id }}">
            <input type="hidden" name="parent_id" id="modal_parent_id" value="">
            
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modal_title">{{ __('Add Member') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('First Name') }}</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Last Name') }}</label>
                            <input type="text" name="last_name" class="form-control" required value="{{ explode(' ', $family->name)[0] }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Gender') }}</label>
                        <select name="gender" class="form-select" required>
                            <option value="male">{{ __('Male') }}</option>
                            <option value="female">{{ __('Female') }}</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Birth Year') }}</label>
                            <input type="number" name="birth_year" class="form-control" min="1000" max="{{ date('Y') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Death Year (nullable)') }}</label>
                            <input type="number" name="death_year" class="form-control" min="1000" max="{{ date('Y') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Photo') }}</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Biography/Description') }}</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Member') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Edit Person Modal -->
<div class="modal fade" id="editPersonModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="editPersonForm" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Edit Member') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('First Name') }}</label>
                            <input type="text" name="first_name" id="edit_first_name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Last Name') }}</label>
                            <input type="text" name="last_name" id="edit_last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Gender') }}</label>
                        <select name="gender" id="edit_gender" class="form-select" required>
                            <option value="male">{{ __('Male') }}</option>
                            <option value="female">{{ __('Female') }}</option>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Birth Year') }}</label>
                            <input type="number" name="birth_year" id="edit_birth_year" class="form-control" min="1000" max="{{ date('Y') }}">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">{{ __('Death Year (nullable)') }}</label>
                            <input type="number" name="death_year" id="edit_death_year" class="form-control" min="1000" max="{{ date('Y') }}">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Photo (Leave empty to keep current)') }}</label>
                        <input type="file" name="photo" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Biography/Description') }}</label>
                        <textarea name="description" id="edit_description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Update Member') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('families.share', $family) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('Share Family Tree') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">{{ __('Enter the phone number of the person you want to share this shajara with. They must be a registered user.') }}</p>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Phone Number') }}</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">+998</span>
                            <input type="text" name="phone_number" class="form-control" required placeholder="901234567" maxlength="9" pattern="[0-9]{9}">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Share Access') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Add Spouse Modal -->
<div class="modal fade" id="addSpouseModal" tabindex="-1">
    <div class="modal-dialog">
        <form id="addSpouseForm" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="spouse_modal_title">{{ __('Add Spouse') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-3">{{ __('Select Spouse from existing family members. Search is filtered by opposite gender.') }}</p>
                    <div class="mb-3">
                        <label class="form-label">{{ __('Available Candidates') }}</label>
                        <select name="spouse_id" id="spouse_select" class="form-select" required>
                            <option value="">{{ __('Searching...') }}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-heart"></i> {{ __('Link Spouse') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
