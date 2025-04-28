/* Glowie Scripts */
document.querySelector('.vendor-toggle')?.addEventListener('click', e => {
	e.preventDefault();
    e.target.classList.toggle('active');
	document.querySelectorAll('tr.vendor').forEach(t => t.classList.toggle('hide'));
});

document.querySelectorAll('td .args-toggle').forEach(e => {
	e.addEventListener('click', t => {
		t.preventDefault();
        e.classList.toggle('active');
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

document.querySelector('.open-file')?.addEventListener('click', e => {
	e.preventDefault();
	openFileWith('vscode', e.currentTarget.getAttribute('href'), e.currentTarget.getAttribute('data-line'));
});

function openFileWith(app, file, line) {
	let link = '';

	switch(app) {
		case 'vscode':
			link = 'vscode://file/' + file + ':' + line;
			break;
	}

	window.open(link, '_blank');
}

hljs.highlightAll();
hljs.initLineNumbersOnLoad({singleLine: true});