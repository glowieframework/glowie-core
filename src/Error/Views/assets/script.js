document.querySelector('.vendor-toggle')?.addEventListener('click', e => {
	e.preventDefault();
	document.querySelectorAll('tr.vendor').forEach(t => t.classList.toggle('hide'));
});

document.querySelectorAll('td .args-toggle').forEach(e => {
	e.addEventListener('click', t => {
		t.preventDefault();
		e.nextElementSibling.classList.toggle('show');
	});
});

document.querySelectorAll('.tabs a').forEach(e => {
	e.addEventListener('click', t => {
		t.preventDefault();
		document.querySelectorAll('.tab').forEach(b => b.classList.remove('active'));
		document.querySelectorAll('.tabs a').forEach(b => b.classList.remove('active'));
		e.classList.add('active');
		document.getElementById(e.getAttribute('href')).classList.add('active');
	});
});