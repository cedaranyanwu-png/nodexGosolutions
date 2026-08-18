<?php
/**
 * settings.php
 *
 * Standalone Payment Credentials & Settings page.
 */

declare(strict_types=1);

$pageTitle = 'Settings & Credentials';
$pageSubtitle = 'Configure Flutterwave checkout API keys and environment parameters.';

require_once __DIR__ . '/admin_header.php';

$conn->createTable('settings');
$allSettings = $conn->select('settings') ?: [];
$flwSettings = $allSettings[0] ?? null;

function maskSecretKey(?string $key): string {
    if (empty($key)) return 'Not Configured';
    $len = strlen($key);
    if ($len <= 15) return str_repeat('•', 12);
    return substr($key, 0, 8) . str_repeat('•', $len - 12) . substr($key, -4);
}
?>

<div class="row g-4">
  <div class="col-md-6">
    <div class="card border-0 shadow-sm rounded-xl">
      <div class="card-header bg-white border-b border-gray-100 py-3">
        <h3 class="text-base font-bold text-gray-800 m-0"><i class="fas fa-key text-red-500 me-2"></i> Flutterwave Payment Credentials</h3>
      </div>
      <div class="card-body p-4 text-start">
        <div id="settingsFeedback" class="alert d-none text-xs rounded-lg p-2.5 mb-3" role="alert"></div>

        <form id="settingsCredForm">
          <div class="mb-3">
            <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Flutterwave Public Key</label>
            <input type="text" id="flwPublicKey" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="FLWPUBK_TEST-..." value="<?php echo maskSecretKey($flwSettings['flw_public_key'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Flutterwave Secret Key</label>
            <input type="text" id="flwSecretKey" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="FLWSECK_TEST-..." value="<?php echo maskSecretKey($flwSettings['flw_secret_key'] ?? ''); ?>" required />
          </div>
          <div class="mb-3">
            <label class="block text-2xs uppercase font-bold text-gray-500 mb-1" style="font-size: 10px;">Flutterwave Encryption Key</label>
            <input type="text" id="flwEncryptionKey" class="form-control text-sm rounded-md px-3 py-2 border-gray-200" placeholder="FLWENCK_TEST-..." value="<?php echo maskSecretKey($flwSettings['flw_encryption_key'] ?? ''); ?>" required />
          </div>
          <button type="submit" id="btnSaveSettings" class="bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs py-2.5 px-4 rounded-lg w-full transition border-0">
            <i class="fas fa-save me-1"></i> Save Configuration Keys
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script>
$(document).ready(function() {
    $('#settingsCredForm').on('submit', function(e) {
        e.preventDefault();
        const feedback = $('#settingsFeedback');
        feedback.addClass('d-none').removeClass('alert-success alert-danger');
        $('#btnSaveSettings').prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i>Saving keys...');

        $.ajax({
            url: '/php/admin_settings_action.php',
            type: 'POST',
            dataType: 'json',
            data: {
                flw_public_key: $('#flwPublicKey').val(),
                flw_secret_key: $('#flwSecretKey').val(),
                flw_encryption_key: $('#flwEncryptionKey').val()
            },
            success: function(res) {
                $('#btnSaveSettings').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Configuration Keys');
                feedback.removeClass('d-none');
                if (res.success) {
                    feedback.addClass('alert-success').text(res.message);
                    setTimeout(() => { window.location.reload(); }, 1200);
                } else {
                    feedback.addClass('alert-danger').text(res.message);
                }
            },
            error: function(xhr) {
                $('#btnSaveSettings').prop('disabled', false).html('<i class="fas fa-save me-1"></i> Save Configuration Keys');
                feedback.removeClass('d-none').addClass('alert-danger').text(xhr.responseJSON ? xhr.responseJSON.message : 'Failed to update settings.');
            }
        });
    });
});
</script>

<?php require_once __DIR__ . '/admin_footer.php'; ?>
