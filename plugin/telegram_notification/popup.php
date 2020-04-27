<style>
	.modal-callback {
		padding:0px;
	}
</style>

<div class="modal-callback">
	<div class="block-3__form" id="modal-callback-form">
            <p class="title">Оставьте заявку и мы с Вами свяжемся</p>
            <p class="subtitle">Наши менеджеры перезвонят Вам в кратчайшие сроки</p>
            <form action="#" id="modalCallback">
              <div class="input-group">
                <div>
					<img src="/local/templates/main2020/img/svg/form-icon-user.svg" />
                </div>
                <input type="text" name="name" placeholder="Ваше имя" required />
              </div>
              <div class="input-group">
                <div>
                  <img src="/local/templates/main2020/img/svg/form-icon-tel.svg" />
                </div>
                <input type="text" name="tel" placeholder="Ваш телефон" required />
              </div>
              <button type="submit" class="btn-white">Оставить заявку</button><br />
              <input type="checkbox" checked /> <small>Согласен на обработку персональных данных</small>
            </form>
   </div>
</div>

<script>
	 modalCallback.onsubmit = async (e) => {
    	e.preventDefault();
		let elem = document.getElementById('modal-callback-form');
		 let response = await fetch('/local/ajax/ajax_callback.php', {
      		method: 'POST',
      		body: new FormData(modalCallback)
    	});

		let result = await response.json();

		elem.innerHTML = '<p class="success">Ваше заявка принята.<br />В ближайшее  время мы с вами свяжемся.</p>';
  	};
</script>