import {ApiClass, Api} from './../api/api.js';

export class PageClass {

	constructor() {
		this.reset();
	}

	reset(){
			this.type = null;
			this.id = 0;
			this.title = null;
			this.html = null;
			this.url = null;
			this.error404 = null;
			this.lang = null;
			this.slug = null;
			this.css = null;
			this.target_id = 0;
			this.seo_tags = null;
			this.seo_title = null;
			this.seo_description = null;
			this.seo_image = null;
	}

	async get(){
		this.reset();
		this.url =window.location.href;

		var pathname = new URL(this.url).pathname;

		var splitUrl = pathname.split('/');
		this.slug = splitUrl[2];

		if(splitUrl[2] == 'product'|| splitUrl[2]=='post'){
			this.type = splitUrl[2];
		}
		else this.type = 'index';

		if(this.type==='post'){
			this.id=16;
		}

		if((splitUrl.length === 2 && splitUrl[1] === '') || (splitUrl.length > 2 && splitUrl[2] === '')){
				this.slug = 'homepage';
				this.id = 1;

		} else {


			if (typeof splitUrl[3] !== 'undefined') {
					var tmp3 = splitUrl[3].split('-');
					this.target_id = parseInt(tmp3[0]);
			} else {
					this.slug = splitUrl[2];
			}
		}

		this.lang = splitUrl[1];


		if(!this.lang){
			this.lang = LANG;
		}

		const Api = new ApiClass();

		if(this.id > 0){
			await Api.get('pages/'+this.id);
		} else {
			await Api.get('pages?slug='+this.slug);
		}



		let page =  Api.response;


		if(Api.statusCode != 200){
			//window.location.href = "/404";
		}


		this.html = page.description;
		this.id= page.id;
		this.title = page.title;
		this.css = page.css;

		this.updateMeta(page);


		if(DEV_MODE){

			let response = await fetch(this.get_template_url());


			if(response.status ==200){
				let html = await response.text(); // Returns it as Promise
				this.html = html;
			}
		}


		return this;
	}



	async load(){
		this.reset();

		//seo title

		await this.get();
		// debugger;

		// GET ALL URL PARAMETERS
		let parameters = new URL(this.url).searchParams;
		parameters.forEach(function(value, key) {

			if(key.endsWith('[]')){
				if(dataProxy.pageUrl[key] == undefined) dataProxy.pageUrl[key] = [];
				dataProxy.pageUrl[key].push(value);
			}else {
					dataProxy.pageUrl[key] = value;
			}
		});

		if(this.type !== 'post' && "post" in dataProxy ){
			dataProxy['post'] = {description: ''};
		}

		if(this.type !== 'product' && "product" in dataProxy ){
			delete dataProxy['product'];
		}



		if(window.location.pathname.includes("products") && "products" in dataProxy ){
			delete dataProxy['products']['result'] ;
			delete dataProxy['products']['pagination'];
		}

		dataProxy['corePage'] = this;



		// debugger;

		if(this.slug != 'checkout'){
			document.getElementById('header').style.removeProperty('display');
			document.getElementById('footer').style.removeProperty('display');
		}else {
			document.getElementById('header').style.display = "none";
			document.getElementById('footer').style.display = "none";
		}


		document.getElementById('pageCss').innerHTML = this.css;

		document.getElementById('main').innerHTML = this.html;

		// INIT SCRIPT FROM NEW HTML
			initScripts();
	}


	gen_editor_url(token){

		let data = {	'token': token,
						'type': this.type,
						'id': this.id};

		return SITEURL+'/editor/cb/editor.php?lang='+LANG+'&i='+btoa(JSON.stringify(data));


	}

	async saveTemplate(){
		const styles = document.getElementById('tailwindCss').getElementsByTagName('style');
		const lastElement = styles[styles.length - 1];
		const lastElementString = lastElement.outerHTML;
		let html = '';

		let response = await fetch(this.get_template_url());

		if(response.status ==200){
				let html = await response.text(); // Returns it as Promise
				this.html = html;
		}


		const reqBody = {
						description: this.html,
						css:lastElementString
					};
		Api.put('pages/'+this.id+"?lang="+this.lang, reqBody);

	}

	async saveHeadCss(){
		const styles = document.getElementById('tailwindCss').getElementsByTagName('style');
		const lastElement = styles[styles.length - 1];
		const lastElementString = lastElement.outerHTML;
		let html = '';

		let response = await fetch(this.get_template_url());

		if(response.status ==200){
				let html = await response.text(); // Returns it as Promise
				this.html = html;
		}


		const reqBody = {

						headCss:lastElementString
					};
		Api.post('admin/settings_web', reqBody);

	}

	get_template_url(){
	
		return SITEURL+"/static/"+this.type+"~"+this.slug+"~"+this.lang+".html";
	}


	async updateMeta(page){

		if(this.slug === 'product'){
			await Api.get('products/'+this.target_id);
			let response = Api.response;
			if(response.seo_description != '') this.seo_description = response.seo_description;
			if(response.seo_title != '') this.seo_title = response.seo_title;
			if(response.seo_tags != '') this.seo_tags = response.seo_tags;
			if(response.images[0] != undefined) this.seo_image = response.images[0].image;
		}

		if(this.slug === 'post'){

			await Api.get('blogPosts/'+this.target_id);
			let response = Api.response;
			if(response.seo_description != '') this.seo_description = response.seo_description;
			if(response.seo_title != '') this.seo_title = response.seo_title;
			if(response.seo_tags != '') this.seo_tags = response.seo_tags;
			if(response.images[0] != undefined) this.seo_image = response.images[0].url;
		}

		if(this.seo_description == null || this.seo_description == '') this.seo_description = `${this.title} ${SITENAME}`;
		if(this.seo_title == null || this.seo_title == '') this.seo_title = `${this.title} ${SITENAME}`;
		if(this.seo_tags == null || this.seo_tags == '') this.seo_tags = `${this.title} ${SITENAME}`.replace(/ /g, ', ');
		if(this.seo_image == null || this.seo_image == '') this.seo_image = LOGO_URL;


		let seo_description = document.getElementsByClassName('seo_description');
		for(const e of seo_description){
			e.content = this.seo_description;
		}

		let seo_title = document.getElementsByClassName('seo_title');
		for(const e of seo_title){
			e.content = this.seo_title;
		}

		let seo_images = document.getElementsByClassName('seo_image');
		for(const e of seo_images){
			e.content = this.seo_image;
		}

		let seo_tags = document.getElementsByClassName('seo_tags');
		for(const e of seo_tags){
			e.content = this.seo_tags;
		}

		document.title = this.seo_title;
	}


	async getAllPages(){

			await Api.get('pages?page=1&limit=5');

			  const resultsPerPage = Api.response.pagination.results_per_page;
			  let currentPage = Api.response.pagination.current_page;
			  let totalPages = Api.response.pagination.total_pages;

			  let allResults = Api.response.result;

			  while (currentPage <= totalPages) {
				  currentPage++;

				try {
					await Api.get('pages?limit='+resultsPerPage+'&page='+currentPage);
				  if (!Api.response.result) {
					throw new Error(`Грешка при извличане на страница ${currentPage}`);
				  }

				  const pageResults = Api.response.result;
				  allResults = allResults.concat(pageResults);


				} catch (error) {
				  console.error(error);
				  break;
				}
			  }

			  return allResults;
	}




};

export let Page = new  PageClass();


//Page.getAllPages()
//  .then((allPages) => {
//    console.log(allPages);
//  })
//  .catch((error) => {
//    console.error(error);
//  });
