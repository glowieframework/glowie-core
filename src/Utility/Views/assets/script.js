document.querySelectorAll('.wrapper .details pre a.toggle').forEach(e => {
	e.addEventListener('click', t => {
		t.preventDefault();
        e.classList.toggle('active');
        e.nextElementSibling.classList.toggle('show');
	})
});

document.querySelector('.wrapper .details pre > .collapse')?.classList?.add('show');