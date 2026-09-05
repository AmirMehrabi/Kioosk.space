import "./business";
import "./auth";

if (document.querySelector('form[role="search"]')) {
const form=document.querySelector('form[role="search"]');
const query=form.elements.query, locationField=form.elements.location;
const cards=[...document.querySelectorAll('#places article')];
const initialQuery = new URLSearchParams(location.search).get('query');
if (initialQuery) { query.value = initialQuery; queueMicrotask(filter); }
const normalize=s=>s.replace(/ي/g,'ی').replace(/ك/g,'ک').replace(/\u200c/g,'').trim();
function filter(){const q=normalize(query.value),l=normalize(locationField.value);let count=0;cards.forEach(card=>{const t=normalize(card.textContent+' تهران صبحانه');card.hidden=!(t.includes(q)&&(!l||t.includes(l)));if(!card.hidden)count++});document.getElementById('no-results').hidden=count>0;}
form.addEventListener('submit',event=>{event.preventDefault();filter();document.getElementById('places').scrollIntoView()});
const categories={'restaurant':'رستوران','cafe':'کافه','shopping':'خرید','doctor':'پزشک','beauty':'زیبایی','home':'خدمات منزل'};
Object.entries(categories).forEach(([key,value])=>document.getElementById('category-'+key+'-link').addEventListener('click',()=>{query.value=value;filter()}));
document.getElementById('all-places-link').addEventListener('click',()=>{query.value='';locationField.value='تهران';filter()});
const popular={'breakfast':'صبحانه','cafe':'کافه','iranian':'غذای ایرانی','dentist':'دندان'};
Object.entries(popular).forEach(([key,value])=>document.getElementById('popular-'+key+'-link').addEventListener('click',()=>{query.value=value;filter()}));

const menuToggle = document.getElementById('menu-toggle');
const menu = document.getElementById('mobile-menu');
menuToggle.addEventListener('click', () => {
    menu.hidden = !menu.hidden;
    menuToggle.setAttribute('aria-expanded', String(!menu.hidden));
});
menu.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
    menu.hidden = true;
    menuToggle.setAttribute('aria-expanded', 'false');
}));
let noticeTimer;
document.querySelectorAll('[data-demo-action]').forEach(link => link.addEventListener('click', event => {
    event.preventDefault();
    const notice = document.getElementById('demo-notice');
    notice.hidden = false;
    clearTimeout(noticeTimer);
    noticeTimer = setTimeout(() => { notice.hidden = true; }, 4500);
}));
document.querySelector('[data-city-select]').addEventListener('click', () => {
    locationField.scrollIntoView({ block: 'center' });
    locationField.focus({ preventScroll: true });
    locationField.select();
});

}
