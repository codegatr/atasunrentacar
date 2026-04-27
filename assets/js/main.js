/* ATA SU Rent A Car - Ana JS */
(function(){
  'use strict';

  // Mobil menu
  document.addEventListener('DOMContentLoaded', function(){
    var btn = document.querySelector('.mobil-menu-btn');
    var menu = document.querySelector('.ana-menu');
    if (btn && menu) {
      btn.addEventListener('click', function(e){
        e.stopPropagation();
        menu.classList.toggle('acik');
        document.body.classList.toggle('menu-acik');
      });
      document.addEventListener('click', function(e){
        if (menu.classList.contains('acik') && !menu.contains(e.target) && !btn.contains(e.target)) {
          menu.classList.remove('acik');
          document.body.classList.remove('menu-acik');
        }
      });
    }

    // Galeri (arac detay)
    var galeriAna = document.querySelector('.galeri-ana img');
    var galeriKucukler = document.querySelectorAll('.galeri-kucuk img');
    if (galeriAna && galeriKucukler.length > 0) {
      galeriKucukler.forEach(function(img){
        img.addEventListener('click', function(){
          galeriAna.src = this.dataset.buyuk || this.src;
          galeriKucukler.forEach(function(i){ i.classList.remove('aktif'); });
          this.classList.add('aktif');
        });
      });
    }

    // Otomatik form valid
    document.querySelectorAll('input[type="date"]').forEach(function(inp){
      if (!inp.min) {
        var bugun = new Date().toISOString().slice(0,10);
        inp.min = bugun;
      }
    });

    // Iade tarihi >= alis tarihi
    var alisInp = document.querySelector('input[name="alis_tarihi"]');
    var iadeInp = document.querySelector('input[name="iade_tarihi"]');
    if (alisInp && iadeInp) {
      alisInp.addEventListener('change', function(){
        var alis = new Date(this.value);
        if (!isNaN(alis)) {
          alis.setDate(alis.getDate() + 1);
          iadeInp.min = alis.toISOString().slice(0,10);
          if (iadeInp.value && new Date(iadeInp.value) <= new Date(this.value)) {
            iadeInp.value = iadeInp.min;
          }
        }
      });
    }
  });
})();
