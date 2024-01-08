document.querySelector('.vendor-toggle')?.addEventListener('click', e => {
	e.preventDefault();
	document.querySelectorAll('tr.vendor').forEach(t => {
		t.classList.toggle('hide')
	})
});

document.querySelectorAll('td .args-toggle').forEach(e => {
	e.addEventListener('click', t => {
		t.preventDefault();
		e.nextElementSibling.classList.toggle('show')
	})
});