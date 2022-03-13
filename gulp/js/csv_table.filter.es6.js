import Drupal from 'Drupal';
Drupal.behaviors.csvTable = {
  // Main function.
  attach(context) {
    const tables = Array.from(context.querySelectorAll('.csv-table'));
    tables.forEach(tbl => {
      const input = tbl.querySelector('.csv-table__search-input');
      input.addEventListener('input', this.handleFilterInput.bind(this));

      const qInitial = this.findGetParameter('q_initial');
      if (null !== qInitial) {
        input.value = qInitial;
        input.dispatchEvent(
          new Event('input')
        );
      }
    });
  },
  // Input event handler.
  handleFilterInput(event) {
    // Declare variables
    let input, filter, table, tr, i, txtValue, thead, noResultsMsg;
    input = event.target;
    table = input.closest('.csv-table');
    filter = input.value.toUpperCase();
    tr = table.querySelectorAll('tbody > tr');

    // Loop through all table rows, and hide those who don't match the search query
    for (i = 0; i < tr.length; i++) {
      txtValue = tr[i].textContent || tr[i].innerText;
      if (txtValue.toUpperCase().indexOf(filter) > -1) {
        tr[i].style.display = '';
      }
      else {
        tr[i].style.display = 'none';
      }
    }

    thead = table.querySelector('thead');
    noResultsMsg = table.querySelector('.csv-table__no-results');
    if (null === this.getFirstVisible(tr)) {
      thead.style.display = 'none';
      noResultsMsg.style.display = 'block';
    }
    else {
      thead.style.display = '';
      noResultsMsg.style.display = 'none';
    }
  },
  // Gets first visible element from given list.
  getFirstVisible(elems) {
    let visible, breakException = {};
    try {
      [].forEach.call(elems, (el) => {
        if (this.isVisible(el)) {
          visible = el;
          throw breakException;
        }
      });
    }
    catch(e) {
      if ( e === breakException ) {
        return visible;
      }
    }
    return null;
  },
  // Find a GET parameter by name from URL.
  findGetParameter(name) {
    let result = null, tmp = [];
    location.search
      .slice(1)
      .split('&')
      .forEach(function (item) {
        tmp = item.split('=');
        if (tmp[0] === name) {
          result = decodeURIComponent(tmp[1]);
        }
      });
    return result;
  },
  // TRUE if el is visible.
  isVisible(el) {
    return el.offsetWidth > 0 && el.offsetHeight > 0;
  }
};
