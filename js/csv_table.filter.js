(function (Drupal) {
  'use strict';
  function _interopDefaultLegacy (e) { return e && typeof e === 'object' && 'default' in e ? e : { 'default': e }; }
  var Drupal__default =              _interopDefaultLegacy(Drupal);
  Drupal__default["default"].behaviors.csvTable = {
    attach: function attach(context) {
      var _this = this;
      var tables = Array.from(context.querySelectorAll('.csv-table'));
      tables.forEach(function (tbl) {
        var input = tbl.querySelector('.csv-table__search-input');
        input.addEventListener('input', _this.handleFilterInput.bind(_this));
        var qInitial = _this.findGetParameter('q_initial');
        if (null !== qInitial) {
          input.value = qInitial;
          input.dispatchEvent(new Event('input'));
        }
      });
    },
    handleFilterInput: function handleFilterInput(event) {
      var input, filter, table, tr, i, txtValue, thead, noResultsMsg;
      input = event.target;
      table = input.closest('.csv-table');
      filter = input.value.toUpperCase();
      tr = table.querySelectorAll('tbody > tr');                                                                                
      for (i = 0; i < tr.length; i++) {
        txtValue = tr[i].textContent || tr[i].innerText;
        if (txtValue.toUpperCase().indexOf(filter) > -1) {
          tr[i].style.display = '';
        } else {
          tr[i].style.display = 'none';
        }
      }
      thead = table.querySelector('thead');
      noResultsMsg = table.querySelector('.csv-table__no-results');
      if (null === this.getFirstVisible(tr)) {
        thead.style.display = 'none';
        noResultsMsg.style.display = 'block';
      } else {
        thead.style.display = '';
        noResultsMsg.style.display = 'none';
      }
    },
    getFirstVisible: function getFirstVisible(elems) {
      var _this2 = this;
      var visible,
          breakException = {};
      try {
        [].forEach.call(elems, function (el) {
          if (_this2.isVisible(el)) {
            visible = el;
            throw breakException;
          }
        });
      } catch (e) {
        if (e === breakException) {
          return visible;
        }
      }
      return null;
    },
    findGetParameter: function findGetParameter(name) {
      var result = null,
          tmp = [];
      location.search.slice(1).split('&').forEach(function (item) {
        tmp = item.split('=');
        if (tmp[0] === name) {
          result = decodeURIComponent(tmp[1]);
        }
      });
      return result;
    },
    isVisible: function isVisible(el) {
      return el.offsetWidth > 0 && el.offsetHeight > 0;
    }
  };
})(Drupal);
