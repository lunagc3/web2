import { HttpInterceptorFn } from '@angular/common/http';

export const authInterceptor: HttpInterceptorFn = (req, next) => {
  // Se obtiene el token JWT guardado en LS
  const token = localStorage.getItem('jwt_token');

  // Se clona la solicitud y se agrega el token (si existe) al encabezado de autorización.
  const authReq = token ? 
      req.clone({ setHeaders: { Authorization: 'Bearer ' + token } }) 
    : req;

  // Se pasa la solicitud clonada al siguiente interceptor o continúa al servidor
  return next(authReq);
};