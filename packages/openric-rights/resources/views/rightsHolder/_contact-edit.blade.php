{{--
  Contact information editor — adapted from Heratio _contact-edit.blade.php (335 lines).
  Table with Contact person + Primary columns, modal editor with tabbed fields.
  OpenRiC uses rights_holder_contacts table instead of contact_information + contact_information_i18n.
--}}

<h3 class="fs-6 mb-2">Related contact information</h3>

<div id="contact-table-editor">
  <div class="table-responsive">
    <table class="table table-bordered mb-0" id="contact-table">
      <thead class="table-light">
        <tr>
          <th>Contact person</th>
          <th>Primary</th>
          <th><span class="visually-hidden">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        @foreach($contacts as $index => $item)
          <tr data-index="{{ $index }}">
            <td>{{ $item->contact_person ?? '' }}</td>
            <td>{{ !empty($item->is_primary) ? 'Yes' : 'No' }}</td>
            <td class="text-nowrap">
              <button type="button" class="btn btn-outline-secondary btn-sm me-1 edit-contact-row" data-index="{{ $index }}">Edit</button>
              <button type="button" class="btn btn-outline-danger btn-sm delete-contact-row" data-index="{{ $index }}">Remove</button>
            </td>
            <input type="hidden" name="contacts[{{ $index }}][id]" value="{{ $item->id ?? '' }}">
            <input type="hidden" name="contacts[{{ $index }}][delete]" value="0" class="delete-flag">
            @foreach(['is_primary', 'contact_person', 'telephone', 'fax', 'email', 'website', 'street_address', 'region', 'country_code', 'postal_code', 'city', 'latitude', 'longitude', 'contact_type', 'note'] as $f)
              <input type="hidden" name="contacts[{{ $index }}][{{ $f }}]" value="{{ $item->$f ?? '' }}">
            @endforeach
          </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="3">
            <button type="button" class="btn btn-outline-primary btn-sm" id="add-contact-row">Add new</button>
          </td>
        </tr>
      </tfoot>
    </table>
  </div>

  <div class="modal fade" id="contactModal" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="h5 modal-title">Contact information</h4>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <ul class="nav nav-pills mb-3" role="tablist">
            <li class="nav-item"><button class="nav-link active" data-bs-toggle="pill" data-bs-target="#pills-main" type="button">Main</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-phys" type="button">Physical location</button></li>
            <li class="nav-item"><button class="nav-link" data-bs-toggle="pill" data-bs-target="#pills-other" type="button">Other</button></li>
          </ul>
          <div class="tab-content">
            <div class="tab-pane fade show active" id="pills-main">
              <div class="mb-3"><div class="form-check"><input type="checkbox" class="form-check-input" id="modal-isPrimary"><label class="form-check-label" for="modal-isPrimary">Primary contact</label></div></div>
              <div class="mb-3"><label class="form-label">Contact person</label><input type="text" class="form-control" id="modal-contactPerson"></div>
              <div class="mb-3"><label class="form-label">Phone</label><input type="text" class="form-control" id="modal-telephone"></div>
              <div class="mb-3"><label class="form-label">Fax</label><input type="text" class="form-control" id="modal-fax"></div>
              <div class="mb-3"><label class="form-label">Email</label><input type="email" class="form-control" id="modal-email"></div>
              <div class="mb-3"><label class="form-label">Website</label><input type="url" class="form-control" id="modal-website"></div>
            </div>
            <div class="tab-pane fade" id="pills-phys">
              <div class="mb-3"><label class="form-label">Street address</label><textarea class="form-control" id="modal-streetAddress" rows="2"></textarea></div>
              <div class="mb-3"><label class="form-label">Region/province</label><input type="text" class="form-control" id="modal-region"></div>
              <div class="mb-3"><label class="form-label">Country code</label><input type="text" class="form-control" id="modal-countryCode" placeholder="e.g. ZA, US, GB"></div>
              <div class="mb-3"><label class="form-label">Postal code</label><input type="text" class="form-control" id="modal-postalCode"></div>
              <div class="mb-3"><label class="form-label">City</label><input type="text" class="form-control" id="modal-city"></div>
              <div class="mb-3"><label class="form-label">Latitude</label><input type="text" class="form-control" id="modal-latitude"></div>
              <div class="mb-3"><label class="form-label">Longitude</label><input type="text" class="form-control" id="modal-longitude"></div>
            </div>
            <div class="tab-pane fade" id="pills-other">
              <div class="mb-3"><label class="form-label">Contact type</label><input type="text" class="form-control" id="modal-contactType"></div>
              <div class="mb-3"><label class="form-label">Note</label><textarea class="form-control" id="modal-note" rows="2"></textarea></div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-outline-success" id="contact-modal-submit">Submit</button>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const tbody = document.querySelector('#contact-table tbody');
  const modal = document.getElementById('contactModal');
  const bsModal = new bootstrap.Modal(modal);
  let editingIndex = null;
  let nextIndex = {{ count($contacts) }};

  const fields = ['isPrimary','contactPerson','telephone','fax','email','website','streetAddress','region','countryCode','postalCode','city','latitude','longitude','contactType','note'];
  const fieldToName = {isPrimary:'is_primary',contactPerson:'contact_person',telephone:'telephone',fax:'fax',email:'email',website:'website',streetAddress:'street_address',region:'region',countryCode:'country_code',postalCode:'postal_code',city:'city',latitude:'latitude',longitude:'longitude',contactType:'contact_type',note:'note'};

  function clearModal() { fields.forEach(function(f) { var el = document.getElementById('modal-'+f); if(el.type==='checkbox') el.checked=false; else el.value=''; }); }
  function getHidden(idx, name) { return tbody.querySelector('input[name="contacts['+idx+']['+name+']"]'); }

  tbody.addEventListener('click', function(e) {
    var editBtn = e.target.closest('.edit-contact-row');
    if(editBtn) { editingIndex = editBtn.dataset.index; clearModal(); fields.forEach(function(f) { var h = getHidden(editingIndex, fieldToName[f]); if(!h) return; var el = document.getElementById('modal-'+f); if(el.type==='checkbox') el.checked = h.value==='1'; else el.value = h.value; }); bsModal.show(); }
    var delBtn = e.target.closest('.delete-contact-row');
    if(delBtn) { var idx = delBtn.dataset.index; var row = tbody.querySelector('tr[data-index="'+idx+'"]'); var idH = getHidden(idx,'id'); if(idH && idH.value) { var df = getHidden(idx,'delete'); if(df) df.value='1'; row.style.display='none'; } else { row.remove(); } }
  });

  document.getElementById('add-contact-row').addEventListener('click', function() {
    editingIndex = nextIndex++;
    clearModal();
    var tr = document.createElement('tr'); tr.dataset.index = editingIndex;
    tr.innerHTML = '<td></td><td>No</td><td class="text-nowrap"><button type="button" class="btn btn-outline-secondary btn-sm me-1 edit-contact-row" data-index="'+editingIndex+'">Edit</button><button type="button" class="btn btn-outline-danger btn-sm delete-contact-row" data-index="'+editingIndex+'">Remove</button></td>';
    ['id','delete','is_primary','contact_person','telephone','fax','email','website','street_address','region','country_code','postal_code','city','latitude','longitude','contact_type','note'].forEach(function(name) {
      var inp = document.createElement('input'); inp.type='hidden'; inp.name='contacts['+editingIndex+']['+name+']'; inp.value = name==='delete'?'0':''; if(name==='delete') inp.className='delete-flag'; tr.appendChild(inp);
    });
    tbody.appendChild(tr); bsModal.show();
  });

  document.getElementById('contact-modal-submit').addEventListener('click', function() {
    fields.forEach(function(f) { var el = document.getElementById('modal-'+f); var h = getHidden(editingIndex, fieldToName[f]); if(!h) return; if(el.type==='checkbox') h.value = el.checked?'1':'0'; else h.value = el.value; });
    var row = tbody.querySelector('tr[data-index="'+editingIndex+'"]');
    if(row) { var cells = row.querySelectorAll('td'); cells[0].textContent = document.getElementById('modal-contactPerson').value; cells[1].textContent = document.getElementById('modal-isPrimary').checked?'Yes':'No'; }
    bsModal.hide(); editingIndex = null;
  });
});
</script>
