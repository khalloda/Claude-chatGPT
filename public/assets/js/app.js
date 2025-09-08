window.App = {
  // Get CSRF token from meta tag
  getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : null;
  },

  // Refresh CSRF token from server
  async refreshCsrfToken() {
    try {
      const response = await fetch('/csrf-refresh', {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      
      if (response.ok) {
        const data = await response.json();
        const meta = document.querySelector('meta[name="csrf-token"]');
        if (meta && data.token) {
          meta.setAttribute('content', data.token);
          return data.token;
        }
      }
    } catch (error) {
      console.warn('Failed to refresh CSRF token:', error);
    }
    return null;
  },

  toast(type,msg){ const el=document.createElement('div');
    el.className=`toast align-items-center text-bg-${type} position-fixed bottom-0 end-0 m-3`;
    el.role='alert'; el.innerHTML=`<div class="d-flex"><div class="toast-body">${msg}</div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    document.body.appendChild(el); new bootstrap.Toast(el,{delay:3500}).show();
  },

  async fetchJson(url,opts={}){
    // Automatically include CSRF token for state-changing requests
    const method = (opts.method || 'GET').toUpperCase();
    const needsCsrf = ['POST', 'PUT', 'PATCH', 'DELETE'].includes(method);
    
    if (needsCsrf) {
      const token = this.getCsrfToken();
      if (token) {
        opts.headers = {
          'X-CSRF-TOKEN': token,
          ...opts.headers
        };
        
        // Also include in form data if using FormData
        if (opts.body instanceof FormData && !opts.body.has('_token')) {
          opts.body.append('_token', token);
        }
        
        // Include in JSON body if present
        if (opts.body && typeof opts.body === 'string' && opts.headers['Content-Type']?.includes('application/json')) {
          try {
            const jsonBody = JSON.parse(opts.body);
            jsonBody._token = token;
            opts.body = JSON.stringify(jsonBody);
          } catch (e) {
            // Ignore parsing errors, body might not be JSON
          }
        }
      }
    }

    const res = await fetch(url, {
      headers: {'X-Requested-With': 'XMLHttpRequest', ...opts.headers}, 
      ...opts
    });
    
    // If we get a 419 (CSRF token mismatch), try refreshing the token once
    if (res.status === 419 && needsCsrf) {
      const newToken = await this.refreshCsrfToken();
      if (newToken) {
        // Retry the request with the new token
        opts.headers['X-CSRF-TOKEN'] = newToken;
        if (opts.body instanceof FormData) {
          opts.body.delete('_token');
          opts.body.append('_token', newToken);
        }
        if (opts.body && typeof opts.body === 'string' && opts.headers['Content-Type']?.includes('application/json')) {
          try {
            const jsonBody = JSON.parse(opts.body);
            jsonBody._token = newToken;
            opts.body = JSON.stringify(jsonBody);
          } catch (e) {
            // Ignore parsing errors
          }
        }
        
        // Retry the request
        const retryRes = await fetch(url, {
          headers: {'X-Requested-With': 'XMLHttpRequest', ...opts.headers}, 
          ...opts
        });
        
        const ct = retryRes.headers.get('content-type')||'';
        if(!ct.includes('application/json')) throw new Error('Invalid response type');
        const data = await retryRes.json(); 
        if(!retryRes.ok||data?.ok===false) throw new Error(data?.error||'Request failed'); 
        return data;
      }
    }

    const ct=res.headers.get('content-type')||'';
    if(!ct.includes('application/json')) throw new Error('Invalid response type');
    const data=await res.json(); if(!res.ok||data?.ok===false) throw new Error(data?.error||'Request failed'); return data;
  }
};
