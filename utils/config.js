var API_BASE = 'http://localhost:8008/mapi_v1/courses';
var API_ABOUT= 'http://localhost:8008/mapi_v2/School/loginSchoolWithSite';

const CONFIG = {
    API_URL: {
        GET_INDEX: API_BASE,
        GET_PAGE: API_ABOUT,
        GET_ARTICLE: API_BASE + '/',
        GET_CATEGORY: API_BASE,
    }
}

module.exports = CONFIG;