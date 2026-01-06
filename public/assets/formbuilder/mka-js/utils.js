// constants
const LS_KEY = (userUuid)=> `mka:formbuilder:${userUuid}:current_form_uuid`;
let FORM_UUID = null;
let FORM_VERSION = 0; // will track optimistic locking version

// DOM helpers
function getHiddenUuid() {
    const el = document.querySelector('#form_uuid');
    return el ? el.value || null : null;
}
function setHiddenUuid(uuid) {
    let el = document.querySelector('#form_uuid');
    if (!el) {
        el = document.createElement('input');
        el.type = 'hidden';
        el.id = 'form_uuid';
        document.body.appendChild(el);
    }
    el.value = uuid;
}

// URL
function setUrlUuid(uuid) {
    const url = new URL(window.location.href);
    url.searchParams.set('form_uuid', uuid);
    history.replaceState({}, '', url);
}
function getUrlUuid() {
    return new URL(window.location.href).searchParams.get('form_uuid');
}

// localStorage
function getStoredUuid(userUuid) {
    try { return localStorage.getItem(LS_KEY(userUuid)); } catch { return null; }
}
function setStoredUuid(userUuid, uuid) {
    try { localStorage.setItem(LS_KEY(userUuid), uuid); } catch {}
}
function clearStoredUuid(userUuid) {
    try { localStorage.removeItem(LS_KEY(userUuid)); } catch {}
}

// single entry point to persist

function persistFormUuid(uuid, userUuid) {
    setUrlUuid(uuid);
    setStoredUuid(userUuid, uuid);
    setHiddenUuid(uuid);
}

// resolve on load: URL > hidden > localStorage
function resolveFormUuid(userUuid) {
    return getUrlUuid() || getHiddenUuid() || getStoredUuid(userUuid) || null;
}
