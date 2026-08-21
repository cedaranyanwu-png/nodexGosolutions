<?php
declare(strict_types=1);
$pageTitle = 'File Workspace';
$pageSubtitle = 'Manage tenant website files with secure, production-bounded operations.';
require_once __DIR__ . '/admin_header.php';
$conn->createTable('websites');
$websites = $conn->select('websites') ?: [];
?>
<div class="material-shell z-depth-1" style="padding:22px;">
  <div class="row valign-wrapper" style="margin-bottom:8px;">
    <div class="col s12 m8"><h5 style="font-weight:800;margin:0;color:#0f172a;">Tenant File Workspaces</h5><p class="grey-text text-darken-1">Select a website, then open its full-screen workspace.</p></div>
    <div class="col s12 m4 right-align"><a href="#adminFileWorkspaceModal" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#adminFileWorkspaceModal"><i class="material-icons left">folder_open</i>Open Workspace</a></div>
  </div>
  <table class="striped responsive-table"><thead><tr><th>Website</th><th>Subdomain</th><th>Status</th><th></th></tr></thead><tbody>
  <?php foreach ($websites as $web): $sub = htmlspecialchars((string)($web['subdomain'] ?? '')); ?>
    <tr><td><?php echo htmlspecialchars((string)($web['name'] ?? '')); ?></td><td class="blue-text text-darken-2"><?php echo $sub; ?></td><td><?php echo ((int)($web['is_suspended'] ?? 0) === 1) ? '<span class="red-text">Suspended</span>' : '<span class="green-text">Active</span>'; ?></td><td class="right-align"><a href="#adminFileWorkspaceModal" class="btn btn-sm btn-primary js-admin-open-files" data-bs-toggle="modal" data-bs-target="#adminFileWorkspaceModal" data-subdomain="<?php echo $sub; ?>">Manage Files</a></td></tr>
  <?php endforeach; ?>
  <?php if (!$websites): ?><tr><td colspan="4" class="center-align grey-text">No website workspaces found.</td></tr><?php endif; ?>
  </tbody></table>
</div>

<div id="adminFileWorkspaceModal" class="modal fade" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen m-0">
  <div class="modal-content" style="height:calc(100% - 70px);padding:0;background:#f8fafc;">
    <div class="blue darken-4 white-text" style="padding:14px 22px;display:flex;align-items:center;justify-content:space-between;gap:12px;">
      <div><h5 style="margin:0;font-weight:800;">File Workspace</h5><small id="adminFmCurrentLabel">Choose a tenant website</small></div>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <div style="display:flex;height:calc(100% - 70px);min-height:0;">
      <aside style="width:280px;background:#0f172a;color:#e2e8f0;padding:18px;overflow:auto;">
        <label class="white-text">Website</label><select id="adminFmWebsiteSelect" class="browser-default" style="margin:8px 0 18px;background:#1e293b;color:#fff;border:1px solid #475569;"><option value="">Select workspace</option><?php foreach ($websites as $web): ?><option value="<?php echo htmlspecialchars((string)$web['subdomain']); ?>"><?php echo htmlspecialchars((string)$web['name']); ?></option><?php endforeach; ?></select>
        <div class="divider" style="background:#334155;margin:10px 0;"></div><button class="btn-flat white-text left-align" style="width:100%;" id="adminFmRoot"><i class="material-icons left">home</i>Root</button>
        <div id="adminFmBreadcrumbs" style="margin-top:12px;font-size:12px;color:#94a3b8;"></div>
        <div style="margin-top:18px;display:grid;gap:8px;"><button class="btn blue darken-2" id="adminFmNewFile">New file</button><button class="btn amber darken-3" id="adminFmNewFolder">New folder</button><label class="btn green darken-2 center-align">Upload / ZIP<input id="adminFmUpload" type="file" multiple hidden></label><button class="btn grey darken-3" id="adminFmRefresh">Refresh</button></div>
      </aside>
      <section style="flex:1;min-width:0;display:flex;flex-direction:column;background:#fff;">
        <div id="adminFmItems" style="flex:0 0 42%;overflow:auto;padding:18px;border-bottom:1px solid #e2e8f0;"><div class="center-align grey-text">Select a website to load files.</div></div>
        <div style="flex:1;display:flex;flex-direction:column;min-height:0;background:#111827;"><div style="padding:10px 16px;display:flex;justify-content:space-between;align-items:center;color:#dbeafe;"><span id="adminFmActiveFile">No file selected</span><div><button class="btn-small grey darken-2" id="adminFmRename">Rename</button><button class="btn-small indigo" id="adminFmCopy">Copy</button><button class="btn-small teal darken-2" id="adminFmMove">Move</button><button class="btn-small red darken-2" id="adminFmDelete">Delete</button><button class="btn-small blue" id="adminFmSave">Save</button></div></div><textarea id="adminFmEditor" style="flex:1;resize:none;border:0;border-top:1px solid #374151;padding:16px;background:#111827;color:#e5e7eb;font:13px/1.6 monospace;outline:none;" placeholder="Select a text file to edit."></textarea></div>
      </section>
    </div>
  </div>
  </div>
</div>
<script>
(function($){
  const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('adminFileWorkspaceModal')); let sub='', path='', active='';
  const esc = v => $('<div>').text(v == null ? '' : String(v)).html();
  const showNotice = message => window.alert(message);
  function load(p=''){ path=p; if(!sub){$('#adminFmItems').html('<div class="center-align grey-text">Choose a website.</div>');return;} $.getJSON('/api/files/list',{subdomain:sub,path:p},r=>{if(!r.success){showNotice(esc(r.message));return;} $('#adminFmCurrentLabel').text(sub+' / '+(p||'root')); $('#adminFmBreadcrumbs').text('/'+p); let h=''; (r.files||[]).forEach(f=>{h+='<div class="collection-item" style="display:flex;justify-content:space-between;align-items:center;cursor:pointer;" data-path="'+esc(f.path)+'" data-dir="'+(f.is_dir?'1':'0')+'"><span><i class="material-icons tiny '+(f.is_dir?'amber-text':'blue-text')+'">'+(f.is_dir?'folder':'description')+'</i> '+esc(f.name)+'</span><small class="grey-text">'+esc(f.formatted_size||'-')+'</small></div>';}); $('#adminFmItems').html(h||'<div class="center-align grey-text">Empty directory.</div>');}); }
  function call(url,data,done){$.post(url,data,r=>{showNotice(esc(r.message||'Done'));if(r.success){done&&done();load(path);}} ,'json').fail(()=>showNotice('Request failed'));}
  $(document).on('click','.js-admin-open-files',function(){sub=$(this).data('subdomain');$('#adminFmWebsiteSelect').val(sub);modal.show();load('');});
  $('#adminFmWebsiteSelect').on('change',function(){sub=$(this).val();load('');}); $('#adminFmRoot').on('click',()=>load('')); $('#adminFmRefresh').on('click',()=>load(path));
  $('#adminFmItems').on('click','.collection-item',function(){const p=$(this).data('path'),d=$(this).data('dir')==='1'; if(d){load(p);return;} active=p;$('#adminFmActiveFile').text(p);$.getJSON('/api/files/read',{subdomain:sub,file:p},r=>$('#adminFmEditor').val(r.content||''));});
  $('#adminFmNewFile').on('click',()=>{const n=prompt('New file name');if(n)call('/api/files/create',{subdomain:sub,name:n,path:path,type:'file'});}); $('#adminFmNewFolder').on('click',()=>{const n=prompt('New folder name');if(n)call('/api/files/create',{subdomain:sub,name:n,path:path,type:'folder'});});
  $('#adminFmRename').on('click',()=>{if(!active)return;const n=prompt('New name',active.split('/').pop());if(n)call('/api/files/rename',{subdomain:sub,old_path:active,new_name:n},()=>{active='';});});
  $('#adminFmCopy').on('click',()=>{if(!active)return;const n=prompt('Destination path including new name',active+'-copy');if(n)call('/api/files/copy',{subdomain:sub,source_path:active,destination_path:n});}); $('#adminFmMove').on('click',()=>{if(!active)return;const n=prompt('Destination path including new name',active);if(n)call('/api/files/move',{subdomain:sub,source_path:active,destination_path:n},()=>{active='';});});
  $('#adminFmDelete').on('click',()=>{if(active&&confirm('Delete '+active+'?'))call('/api/files/delete',{subdomain:sub,path:active},()=>{active='';$('#adminFmEditor').val('');});}); $('#adminFmSave').on('click',()=>{if(active)call('/api/files/save',{subdomain:sub,file:active,content:$('#adminFmEditor').val()});});
  $('#adminFmUpload').on('change',function(){const fd=new FormData();fd.append('subdomain',sub);fd.append('path',path);Array.from(this.files).forEach(f=>fd.append('file[]',f));$.ajax({url:'/api/files/upload',method:'POST',data:fd,processData:false,contentType:false,dataType:'json',success:r=>{showNotice(esc(r.message));load(path);}});this.value='';});
})(jQuery);
</script>
<?php require_once __DIR__ . '/admin_footer.php'; ?>
