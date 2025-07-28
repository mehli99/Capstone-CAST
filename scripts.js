/*
 * Corrected and Refactored scripts.js
 * All code is inside a single DOMContentLoaded listener.
 * Page-specific code is wrapped in "if (element)" blocks to prevent errors.
 */
document.addEventListener('DOMContentLoaded', () => {

  // --- 1. GLOBAL / SITE-WIDE LOGIC ---
  // This code runs on every page that includes this script.

  // Logo click navigates to homepage
  if (!document.body.classList.contains('auth-page')) {
    document.querySelectorAll('.logo-box img').forEach(logo => {
      logo.style.cursor = 'pointer';
      logo.addEventListener('click', () => window.location.href = 'homepage.html');
    });
  }

  // Generic "edit" button toggles readonly attribute
  document.querySelectorAll('.edit-toggle').forEach(btn => {
    const input = btn.closest('.edit-field')?.querySelector('input');
    if (!input) return;
    btn.addEventListener('click', () => {
      const locked = input.hasAttribute('readonly');
      if (locked) {
        input.removeAttribute('readonly');
        input.focus();
      } else {
        input.setAttribute('readonly', true);
        input.blur();
      }
    });
  });

  // Avatar profile menu
  const avatarBtn = document.querySelector('.avatar-btn');
  const dashCard = document.querySelector('.dashboard-card');
  if (avatarBtn && dashCard) {
    let menu = null;
    const buildMenu = () => {
      const div = document.createElement('div');
      div.className = 'profile-menu';
      div.innerHTML = `
        <ul>
          <li><a href="accountSettings.html">Account Settings</a></li>
          <li><a href="#">Subscription</a></li>
          <li><a href="support.html">Contact Support</a></li>
          <li><a href="#" class="logout-link">Log Out</a></li>
        </ul>`;
      dashCard.appendChild(div);
      div.querySelector('.logout-link').addEventListener('click', e => {
        e.preventDefault();
        window.location.href = 'index.html';
      });
      return div;
    };
    avatarBtn.addEventListener('click', e => {
      if (!menu) menu = buildMenu();
      menu.classList.toggle('show');
      e.stopPropagation();
    });
    document.addEventListener('click', e => {
      if (menu && !menu.contains(e.target) && !avatarBtn.contains(e.target)) {
        menu.classList.remove('show');
      }
    });
  }


  // --- 2. PAGE-SPECIFIC LOGIC ---
  // These blocks only run if their specific form exists on the current page.

  // --- Logic for Account Settings page ---
  const settingsForm = document.getElementById('settingsForm');
  if (settingsForm) {
    const saveMsg = document.getElementById('saveMsg');

    // Function to fetch user data and populate the form
    const loadAccountSettings = async () => {
      try {
        const response = await fetch('accountSettings.php');
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const userData = await response.json();
        if (userData) {
          settingsForm.email.value = userData.email || '';
          settingsForm.first_name.value = userData.first_name || '';
          settingsForm.last_name.value = userData.last_name || '';
          settingsForm.city.value = userData.city || '';
          settingsForm.state.value = userData.state || '';
          settingsForm.setting.value = userData.setting || '';
        }
      } catch (error) {
        console.error("Could not load account settings:", error);
      }
    };
    loadAccountSettings();

    // Password change logic
    const revealBtn = document.getElementById('showPwdChange');
    const pwdBlock = document.getElementById('pwdChangeBlock');
    const oldIn = settingsForm.oldPassword,
          newIn = settingsForm.newPassword,
          confIn = settingsForm.confirmPassword;

    [oldIn, newIn, confIn].forEach(i => i.required = false);

    const validatePasswords = () => {
      [newIn, confIn].forEach(i => i.setCustomValidity(''));
      if (newIn.value && newIn.value === oldIn.value) newIn.setCustomValidity('New password must differ');
      if (confIn.value && confIn.value !== newIn.value) confIn.setCustomValidity('Does not match');
    };
    revealBtn.addEventListener('click', () => {
      pwdBlock.style.display = 'block';
      newIn.focus();
      revealBtn.style.display = 'none';
      [oldIn, newIn, confIn].forEach(i => i.required = true);
    });
    [oldIn, newIn, confIn].forEach(i => i.addEventListener('input', validatePasswords));

    settingsForm.addEventListener('submit', function(event) {
      event.preventDefault();
      const isChangingPassword = pwdBlock.style.display !== 'none';
      if (isChangingPassword) {
        validatePasswords();
        if (!settingsForm.checkValidity()) {
          settingsForm.reportValidity();
          return;
        }
      }
      const formData = new FormData(settingsForm);
      fetch('accountSettings.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.text())
        .then(result => {
          if (result.trim() === 'success') {
            saveMsg.style.display = 'block';
            setTimeout(() => saveMsg.style.display = 'none', 3000);
            loadAccountSettings();
            if (isChangingPassword) {
              oldIn.value = ''; newIn.value = ''; confIn.value = '';
              pwdBlock.style.display = 'none';
              revealBtn.style.display = 'inline-block';
              [oldIn, newIn, confIn].forEach(i => i.required = false);
            }
          } else {
            alert('An error occurred. Please try again.');
            console.error('Server response:', result);
          }
        })
        .catch(error => {
          console.error('Error submitting form:', error);
          alert('A network error occurred. Please check your connection.');
        });
    });
  }


const infoForm = document.getElementById('infoForm');
if (infoForm) {
  const backLink = document.getElementById('backLink');

  const loadPatientInfo = () => {
    const params = new URLSearchParams(window.location.search);
    
    
    infoForm.elements['id'].value = params.get('id') || '';
    infoForm.elements['first_Name'].value = params.get('first_Name') || '';
    infoForm.elements['middle_Initial'].value = params.get('middle_Initial') || '';
    infoForm.elements['last_Name'].value = params.get('last_Name') || '';
    infoForm.elements['dob'].value = params.get('dob') || '';
    infoForm.elements['sex'].value = params.get('sex') || '';
    infoForm.elements['email'].value = params.get('email') || '';

    if (backLink) {
      backLink.href = `patientHomepage.html?${params.toString()}`;
    }
  };
  loadPatientInfo();

  infoForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const formData = new FormData(infoForm);
    const saveMsg = document.getElementById('saveMsg');
    fetch('updatePatient.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(result => {
        if (result.trim() === 'success') {
          saveMsg.style.display = 'block';
          setTimeout(() => saveMsg.style.display = 'none', 3000);
          
          const newParams = new URLSearchParams(formData);
          const name = `${formData.get('last_Name').toUpperCase()}, ${formData.get('first_Name').charAt(0).toUpperCase()}`;
          newParams.set('name', name);
          if (backLink) {
            backLink.href = `patientHomepage.html?${newParams.toString()}`;
          }
        } else {
          alert('Error: Could not save changes. ' + result);
        }
      })
      .catch(error => {
        console.error('Submit Error:', error);
        alert('A network error occurred.');
      });
  });
}

  const supportForm = document.getElementById('supportForm');
  if (supportForm) {
    supportForm.addEventListener('submit', e => {
      e.preventDefault();
      const banner = document.getElementById('sendBanner');
      banner.style.display = 'block';
      banner.classList.add('show');
      setTimeout(() => banner.classList.remove('show'), 3000);
      supportForm.reset();
    });
  }
  
});

const infoForm = document.getElementById('infoForm');
if (infoForm) {
  const backLink = document.getElementById('backLink');

  const loadPatientInfo = () => {
    const params = new URLSearchParams(window.location.search);
    
    infoForm.id.value = params.get('id') || '';
    infoForm.first_Name.value = params.get('first_Name') || '';
    infoForm.middle_Initial.value = params.get('middle_Initial') || '';
    infoForm.last_Name.value = params.get('last_Name') || '';
    infoForm.dob.value = params.get('dob') || '';
    infoForm.sex.value = params.get('sex') || '';
    infoForm.email.value = params.get('email') || '';

    if (backLink) {
      backLink.href = `patientHomepage.html?${params.toString()}`;
    }
  };
  loadPatientInfo();

  infoForm.addEventListener('submit', (event) => {
    event.preventDefault();
    const formData = new FormData(infoForm);
    const saveMsg = document.getElementById('saveMsg');
    fetch('updatePatient.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.text())
      .then(result => {
        if (result.trim() === 'success') {
          saveMsg.style.display = 'block';
          setTimeout(() => saveMsg.style.display = 'none', 3000);
          
          const newParams = new URLSearchParams(formData);
          const name = `${formData.get('last_Name').toUpperCase()}, ${formData.get('first_Name').charAt(0).toUpperCase()}`;
          newParams.set('name', name);
          if (backLink) {
            backLink.href = `patientHomepage.html?${newParams.toString()}`;
          }
        } else {
          alert('Error: Could not save changes. ' + result);
        }
      })
      .catch(error => {
        console.error('Submit Error:', error);
        alert('A network error occurred.');
      });
  });
}