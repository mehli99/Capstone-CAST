document.addEventListener('DOMContentLoaded', function() {
  
  async function loadAccountSettings() {
    try {
      const response = await fetch('accountSettings.php');
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const userData = await response.json();

      if (userData) {
        document.querySelector('input[name="email"]').value = userData.email || '';
        document.querySelector('input[name="firstName"]').value = userData.first_name || '';
        document.querySelector('input[name="lastName"]').value = userData.last_name || '';
        document.querySelector('input[name="city"]').value = userData.city || '';
        document.querySelector('input[name="state"]').value = userData.state || '';
        document.querySelector('input[name="setting"]').value = userData.setting || '';
      }

    } catch (error) {
      console.error("Could not load account settings:", error);
    }
  }

  loadAccountSettings();

  const settingsForm = document.getElementById('settingsForm');
  const saveMsg = document.getElementById('saveMsg');

  settingsForm.addEventListener('submit', function(event) {
    event.preventDefault(); 
    
    
    const formData = new FormData(settingsForm);

   
    fetch('accountSettings.php', {
      method: 'POST',
      body: formData
    })
    .then(response => response.text())
    .then(result => {
      if (result === 'success') {
        saveMsg.style.display = 'block'; 
        setTimeout(() => {
          saveMsg.style.display = 'none';
        }, 3000);
      }
    })
    .catch(error => {
      console.error('Error saving settings:', error);
    });
  });
}); 
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
