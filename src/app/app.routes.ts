import { Routes } from '@angular/router';
import { AppComponent } from './app.component';
import { HomeComponent } from './home/home.component';
import { PrivadoComponent } from './privado/privado.component';
import { LoginComponent } from './login/login.component';
//import { RegisterComponent } from './register/register.component';
//import { PublishComponent } from './publish/publish.component';
//import { AdminComponent } from './admin/admin.component';
import { authGuard } from './auth.guard';

export const routes: Routes = [
  { path: '', component: HomeComponent },
  { path: 'privado', component: PrivadoComponent, canActivate: [authGuard] },
  { path: 'login', component: LoginComponent },
  // { path: 'register', component: RegisterComponent },
  //{ path: 'publish', component: PublishComponent, canActivate: [authGuard] },
  //{ path: 'admin', component: AdminComponent, canActivate: [authGuard] },
];
