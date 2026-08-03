// app.js - Sidebar view switching and AJAX interactions
$(document).ready(function () {
alert("cedar");
    fetchData();

    // Navigation Switcher
    $('.nav-link-item').on('click', function (e) {
        e.preventDefault();
        $('.nav-link-item').removeClass('active');
        $(this).addClass('active');

        let target = $(this).attr('href').replace('#view-', '#section-');
        let title = $(this).text().trim();

        $('#page-title').text(title);
        $('.view-section').addClass('d-none');
        $(target).removeClass('d-none');
    });

    // Fetch Projects & Databases
    function fetchData() {
        $.ajax({
            url: 'api.php?action=list',
            type: 'GET',
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    $('#stat-count').text(res.stats.count);
                    $('#stat-storage').text(res.stats.total_storage);
                    $('#stat-db-count').text(res.stats.db_count);

                    // Render Projects
                    let projRows = '';
                    $.each(res.data, function (i, p) {
                        projRows += `
                            <tr>
                                <td><a href="${p.path}" target="_blank" class="fw-bold text-decoration-none">${p.slug}.nodexplatform.com.ng</a></td>
                                <td><span class="badge bg-success-subtle text-success">Online</span></td>
                                <td><code>${p.path}</code></td>
                                <td>${p.size}</td>
                                <td>${p.created_at}</td>
                                <td class="text-end"><button class="btn btn-sm btn-outline-danger btn-delete-project" data-id="${p.id}">Delete</button></td>
                            </tr>`;
                    });
                    $('#projects-table-body').html(projRows || '<tr><td colspan="6" class="text-center py-3">No active subdomains</td></tr>');

                    // Render Databases
                    let dbRows = '';
                    $.each(res.databases, function (i, db) {
                        dbRows += `
                            <tr>
                                <td class="fw-bold">${db.name}</td>
                                <td><span class="badge bg-secondary">${db.type.toUpperCase()}</span></td>
                                <td><code>${db.path}</code></td>
                                <td>${db.size}</td>
                                <td class="text-end"><button class="btn btn-sm btn-outline-danger btn-delete-db" data-name="${db.name}">Delete</button></td>
                            </tr>`;
                    });
                    $('#db-table-body').html(dbRows || '<tr><td colspan="5" class="text-center py-3">No databases created</td></tr>');
                }
            }
        });
    }

    // Deploy Form Submit
    $('#form-create-project').on('submit', function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        $.ajax({
            url: 'api.php?action=create',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    $('#createProjectModal').modal('hide');
                    $('#form-create-project')[0].reset();
                    fetchData();
                } else alert(res.message);
            }
        });
    });

    // Create Database
    $('#form-create-db').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: 'api.php?action=create_db',
            type: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    $('#form-create-db')[0].reset();
                    fetchData();
                } else alert(res.message);
            }
        });
    });

    // Delete Database
    $(document).on('click', '.btn-delete-db', function () {
        if (!confirm('Delete this database?')) return;
        $.ajax({
            url: 'api.php?action=delete_db',
            type: 'POST',
            data: { name: $(this).data('name') },
            dataType: 'json',
            success: function () { fetchData(); }
        });
    });
});