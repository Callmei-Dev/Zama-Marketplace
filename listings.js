// assets/js/listings.js
const apiBase = '/api';
const productGrid = document.getElementById('productGrid');
const searchInput = document.getElementById('searchInput');
const categoriesContainer = document.getElementById('categoriesContainer');

async function fetchCategories(){
  // static categories for now; could be fetched from /api/categories.php
  const cats = ['Tops','Hats','Jewellery','Beauty','Jackets','Books','Electronics','Games','Fitness'];
  categoriesContainer.innerHTML = '';
  cats.forEach(c=>{
    const btn = document.createElement('button');
    btn.textContent = c;
    btn.addEventListener('click', ()=> loadListings({category:c.toLowerCase()}));
    categoriesContainer.appendChild(btn);
  });
}

let searchTimer = null;
if (searchInput) {
  searchInput.addEventListener('input', ()=>{
    clearTimeout(searchTimer);
    searchTimer = setTimeout(()=> loadListings({q: searchInput.value}), 300);
  });
}

async function loadListings({page=1, limit=24, category=null, q=null} = {}){
  const params = new URLSearchParams({page, limit});
  if (category) params.set('category', category);
  if (q) params.set('q', q);
  const res = await fetch(`${apiBase}/listings.php?${params.toString()}`);
  if (!res.ok) {
    productGrid.innerHTML = '<p class="error">Failed to load listings</p>';
    return;
  }
  const data = await res.json();
  renderListings(data.items || []);
}

function formatPrice(cents){
  // If price is already in rands (not cents), adjust accordingly.
  // This keeps previous behavior: if stored in cents, divide by 100.
  const num = Number(cents);
  if (Number.isInteger(num) && num > 1000) {
    // assume cents
    return `R ${ (num/100).toFixed(2) }`;
  }
  return `R ${ num.toFixed(2) }`;
}

function renderListings(items){
  productGrid.innerHTML = '';
  if (!items.length) {
    productGrid.innerHTML = '<p>No items found</p>';
    return;
  }

  items.forEach(it=>{
    const card = document.createElement('article');
    card.className = 'product-card';

    // Build image path; API returns main_image as file path or empty string
    const imgSrc = it.main_image ? `${it.main_image}` : '/assets/img/placeholder.png';

    // Create image element
    const img = document.createElement('img');
    img.src = imgSrc;
    img.alt = escapeHtml(it.name);
    img.loading = 'lazy';

    // Meta container
    const meta = document.createElement('div');
    meta.className = 'meta';
    const nameDiv = document.createElement('div');
    nameDiv.className = 'name';
    nameDiv.textContent = it.name;
    const priceDiv = document.createElement('div');
    priceDiv.className = 'price';
    priceDiv.textContent = formatPrice(it.price);

    meta.appendChild(nameDiv);
    meta.appendChild(priceDiv);

    // View button (links to product page)
    const btnWrap = document.createElement('div');
    btnWrap.className = 'card-actions';
    const viewBtn = document.createElement('a');
    viewBtn.className = 'btn view-btn';
    viewBtn.href = `/product.php?id=${encodeURIComponent(it.id)}`;
    viewBtn.textContent = 'View';
    btnWrap.appendChild(viewBtn);

    // Assemble card
    card.appendChild(img);
    card.appendChild(meta);
    card.appendChild(btnWrap);

    productGrid.appendChild(card);
  });
}

function escapeHtml(s){ return String(s).replace(/[&<>"']/g, c=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' })[c]); }

document.addEventListener('DOMContentLoaded', ()=>{
  fetchCategories();
  loadListings();
});

