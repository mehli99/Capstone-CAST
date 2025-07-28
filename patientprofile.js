document.addEventListener('DOMContentLoaded', () => {
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
        backLink.href = `patientHomepage.php?${params.toString()}`;
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
              backLink.href = `patientHomepage.php?${newParams.toString()}`;
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