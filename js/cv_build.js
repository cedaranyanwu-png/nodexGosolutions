$(document).ready(function() {

  // Initial Sync
  updatePreview();

  // Dynamic Event Listeners for Live Preview Sync
  $('#resumeForm').on('input change', 'input, textarea', function() {
    updatePreview();
  });

  // Switch Resume Template Classes
  $('input[name="templateChoice"]').on('change', function() {
    const chosenTemplate = $(this).val();
    $('#resumePreview')
      .removeClass('template-modern template-classic template-minimal')
      .addClass(chosenTemplate);
  });

  // Update Live Preview Paper
  function updatePreview() {
    $('#pvFullName').text($('#inFullName').val() || 'Your Name');
    $('#pvJobTitle').text($('#inJobTitle').val() || 'Job Title');
    $('#pvEmail').html(`<i class="fa-solid fa-envelope me-1"></i> ${$('#inEmail').val()}`);
    $('#pvPhone').html(`<i class="fa-solid fa-phone me-1"></i> ${$('#inPhone').val()}`);
    $('#pvLocation').html(`<i class="fa-solid fa-location-dot me-1"></i> ${$('#inLocation').val()}`);
    $('#pvSummary').text($('#inSummary').val());

    // Sync Experience Blocks
    const expContainer = $('#pvExperience');
    expContainer.empty();
    $('.exp-block').each(function() {
      const title = $(this).find('.exp-title').val();
      const company = $(this).find('.exp-company').val();
      const dates = $(this).find('.exp-dates').val();
      const desc = $(this).find('.exp-desc').val();

      if (title || company) {
        expContainer.append(`
          <div class="mb-3">
            <div class="d-flex justify-content-between font-semibold">
              <strong class="text-dark">${title} ${company ? '| ' + company : ''}</strong>
              <span class="text-muted small">${dates}</span>
            </div>
            <p class="small text-secondary mb-0 mt-1">${desc}</p>
          </div>
        `);
      }
    });

    // Sync Education Blocks
    const eduContainer = $('#pvEducation');
    eduContainer.empty();
    $('.edu-block').each(function() {
      const degree = $(this).find('.edu-degree').val();
      const school = $(this).find('.edu-school').val();
      const dates = $(this).find('.edu-dates').val();

      if (degree || school) {
        eduContainer.append(`
          <div class="mb-2">
            <div class="d-flex justify-content-between">
              <strong>${degree}</strong>
              <span class="text-muted small">${dates}</span>
            </div>
            <div class="small text-secondary">${school}</div>
          </div>
        `);
      }
    });

    // Sync Skills Badges
    const skillsRaw = $('#inSkills').val().split(',');
    const skillsContainer = $('#pvSkills');
    skillsContainer.empty();
    skillsRaw.forEach(skill => {
      const trimmed = skill.trim();
      if (trimmed) {
        skillsContainer.append(`<span class="badge bg-light text-dark border">${trimmed}</span>`);
      }
    });
  }

  // Add Dynamic Experience Field Block
  $('#addExpBtn').on('click', function() {
    $('#experienceFields').append(`
      <div class="exp-block mb-3 border-bottom pb-3">
        <input type="text" class="form-control exp-title mb-2" placeholder="Job Title">
        <input type="text" class="form-control exp-company mb-2" placeholder="Company Name">
        <input type="text" class="form-control exp-dates mb-2" placeholder="Dates">
        <textarea class="form-control exp-desc mb-2" rows="3" placeholder="Key responsibilities"></textarea>
        <button type="button" class="btn btn-sm btn-outline-danger remove-block"><i class="fa-solid fa-trash"></i> Remove</button>
      </div>
    `);
  });

  // Add Dynamic Education Field Block
  $('#addEduBtn').on('click', function() {
    $('#educationFields').append(`
      <div class="edu-block mb-3 border-bottom pb-3">
        <input type="text" class="form-control edu-degree mb-2" placeholder="Degree / Certificate">
        <input type="text" class="form-control edu-school mb-2" placeholder="University / School">
        <input type="text" class="form-control edu-dates mb-2" placeholder="Graduation Year">
        <button type="button" class="btn btn-sm btn-outline-danger remove-block"><i class="fa-solid fa-trash"></i> Remove</button>
      </div>
    `);
  });

  // Remove Dynamic Blocks
  $(document).on('click', '.remove-block', function() {
    $(this).closest('.exp-block, .edu-block').remove();
    updatePreview();
  });

  // Cover Letter Generator Logic
  $('#buildCoverLetterBtn').on('click', function() {
    const name = $('#inFullName').val() || 'Candidate';
    const title = $('#inJobTitle').val() || 'Professional';
    const company = $('#clCompany').val() || 'your company';
    const manager = $('#clManager').val() || 'Hiring Manager';

    const letter = `Dear ${manager},

I am writing to express my strong interest in the ${title} role at ${company}. With a proven track record of background and hands-on achievements in my field, I am eager to contribute to your team's upcoming goals.

Throughout my career, I have honed expertise in ${$('#inSkills').val()}. My approach centers around solving complex operational challenges, driving key metrics, and collaborating effectively across teams.

I would welcome the opportunity to discuss how my background and technical skills align with the vision at ${company}. Thank you for your time and consideration.

Sincerely,

${name}
${$('#inEmail').val()} | ${$('#inPhone').val()}`;

    $('#clOutput').val(letter);
  });

  // Copy Cover Letter
  $('#copyCoverLetterBtn').on('click', function() {
    const text = $('#clOutput').val();
    if (text) {
      navigator.clipboard.writeText(text);
      alert('Cover letter copied to clipboard!');
    }
  });

  // Download PDF using html2pdf.js
  $('#downloadPdfBtn').on('click', function() {
    const element = document.getElementById('resumePreview');
    const name = $('#inFullName').val().replace(/\s+/g, '_') || 'Resume';

    const opt = {
      margin:       0.4,
      filename:     `${name}_CV.pdf`,
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2, useCORS: true },
      jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save();
  });

});