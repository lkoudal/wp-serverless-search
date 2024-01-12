/**
 * WP Serverless Search
 * A static search plugin for WordPress.
 */


var wpServerlessSearch = (function () {
  const searchFeed = searchParams.uploadDir + '/wp-sls/export.json';
  const urlParams = window.location.search;
  const searchModalSelector = 'wp-sls-search-modal';
  const searchModalInput = '.wp-sls-search-field';
  const searchForm = searchParams.searchForm;
  const searchFormInput = searchParams.searchFormInput;

  /**
   * Sync search input with search modal
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Sunday, January 7th, 2024.
   * @return	void
   */
  function syncSearchFields() {
    jQuery(searchFormInput).keyup(function () {
      jQuery(searchModalInput).val(jQuery(this).val());
    });
  }


  /**
   * postUrl.
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Sunday, January 7th, 2024.
   * @param	mixed	url	
   * @return	mixed
   */
  function postUrl(url) {
    return url;
  }

  /**
   * Test for search query based on URL
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Sunday, January 7th, 2024.
   * @return	void
   */
  function urlQuery() {
    if (!searchQueryParams()) {
      return;
    }

searchPosts();
  }

  /**
   * addQueryToSearchModal.
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Sunday, January 7th, 2024.
   * @return	void
   */
  function addQueryToSearchModal() {
    if (!searchQueryParams()) {
      return;
    }

    var el = document.querySelectorAll(searchModalInput);
    [].forEach.call(el, function (el) {
      el.value = searchQueryParams();
    });
  }

  /**
   * searchQueryParams.
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Sunday, January 7th, 2024.
   * @param	mixed	url	Default: urlParams
   * @return	mixed
   */
  function searchQueryParams(url = urlParams) {
    url = url.split('+').join(' ');

    var params = {},
      tokens,
      re = /[?&]?([^=]+)=([^&]*)/g;

    while (tokens = re.exec(url)) {
      params[decodeURIComponent(tokens[1])] = decodeURIComponent(tokens[2]);
    }

    return params.s;
  }


  /**
   * onSearchSubmit.
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Sunday, January 7th, 2024.
   * @return	void
   */
  function onSearchSubmit() {
    var el = document.querySelectorAll(searchForm);
    [].forEach.call(el, function (e) {
      e.addEventListener("submit", function (e) {
        e.preventDefault();
//        launchSearchModal();
        console.log('search submit');
});
    });
  }

  /**
   * onSearchInput.
   *
   * @author	Lars Koudal
   * @since	v0.0.1
   * @version	v1.0.0	Sunday, January 7th, 2024.
   * @return	void
   */
  function onSearchInput() {
    var el = document.querySelectorAll(searchForm);
    [].forEach.call(el, function (e) {
      e.addEventListener("input", function (e) {
        // fire on search input
        console.log('Search input changed'); // Add your functionality here

      });
    });
  }
  

/**
 * @var		async	functio
 * @global
 */
async function searchPosts() {
  var search = null;
  var data = await jQuery.ajax(searchFeed, {
    dataType: "json"
  });

  var searchArray = [];

  data.forEach(function (item) {
    if (!item.title) {
      return;
    }

    searchArray.push({
      "title": item.title,
      "description": item.description ? item.description : "",
      "content": item.content,
      "link": postUrl(item.link)
    });
  });

  var searchOptions = {
    shouldSort: true,
    threshold: 0.1,
    location: 0,
    distance: 100,
    maxPatternLength: 32,
    minMatchCharLength: 1,
    keys: [{
      name: 'title',
      weight: 0.5
    }, {
      name: 'description',
      weight: 0.5
    }]
  };

  var fuse = new Fuse(searchArray, searchOptions);

  var $searchInput = jQuery(searchParams.searchFormInput);

  $searchInput.each(function () {

    jQuery(this).on('input', async function () {

      console.log('Search term: ', jQuery(this).val());
      var search = fuse.search(jQuery(this).val(), searchOptions);

      // Limit the number of results displayed
      search = search.slice(0, 20);

      // Get the results container relative to the current input field
      var $res = jQuery(this).siblings('.wp-sls-search-results');
      $res.empty();

      if (jQuery(this).val().length < 1) return;
      if (search[0] === undefined) {
        $res.append('<h5>No results</h5>');
      } else {
        $res.append('<h5>' + search.length + ' results:</h5>');
      }

      search.forEach(function (data) {

        var postContentData = {
          title: data.item.title,
          excerpt: data.item.description,
          link: data.item.link
        };

        $res.append(postContent(postContentData));
      });
    });
  });
}


  
    /**
     * postContent.
     *
     * @author	Lars Koudal
     * @since	v0.0.1
     * @version	v1.0.0	Sunday, January 7th, 2024.
     * @param	mixed	post	
     * @return	void
     */
    function postContent(post) {

      return `
        <article>
          <header>
            <h4><a href='${post.link}' rel='bookmark'>${post.title}</a></h4>
            ${post.excerpt ? `<p>${post.excerpt}</p>` : ''}
          </header>
        </article>`;
    }

      // Uncomment the function call

  onSearchInput();
  searchPosts();
  onSearchSubmit();
  addQueryToSearchModal();
  urlQuery();
  syncSearchFields();

})();
