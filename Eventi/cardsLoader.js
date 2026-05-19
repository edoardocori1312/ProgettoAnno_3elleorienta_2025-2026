/* cardsLoader.js
   - Fa fetch a /api/cards.php
   - Popola le card con id `card_1`, `card_2`, ...
   - Gestisce errori e fallback
*/

async function loadCards(){
  const endpoint = 'events.php';
  try{
    const res = await fetch(endpoint, { cache: 'no-store' });
    if(!res.ok) throw new Error('Network response not ok ' + res.status);
    const data = await res.json();
    if(!Array.isArray(data)) throw new Error('Invalid JSON from server');

    // Popola fino a 3 card: card_1, card_2, card_3
    data.slice(0,3).forEach((item, idx) => {
      const id = 'card_' + (idx+1);
      const el = document.getElementById(id);
      if(!el) return;
      const img = el.querySelector('img.card-img-top');
      const title = el.querySelector('.card-title');
      const text = el.querySelector('.card-text');

      if(img && item.image) img.src = item.image;
      if(title) title.textContent = item.title || '';
      if(text) text.textContent = item.description || '';

      // opzionale: salva lat/lng come data-attribute per uso futuro
      if(item.lat !== undefined && item.lng !== undefined){
        el.dataset.lat = item.lat;
        el.dataset.lng = item.lng;
      }
    });

  }catch(err){
    console.error('Errore caricamento cards:', err);
    // fallback: non fare nulla (le card mantengono i placeholder nel markup)
  }
}

// Avvia il loader dopo il DOMContentLoaded
if(document.readyState === 'loading'){
  document.addEventListener('DOMContentLoaded', loadCards);
} else {
  loadCards();
}
