import { Component } from '@angular/core';
import { CommonModule } from "@angular/common";
import { FormsModule } from "@angular/forms";
import { AuthService } from '../auth.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [FormsModule, CommonModule],
  templateUrl: './login.component.html',
  styleUrl: './login.component.css'
})
export class LoginComponent {
  public email: string = '';
  public pass: string = '';
  public error: boolean = false;

  constructor(private authService: AuthService, private router: Router) {}

  login(): void {
    console.log(this.email, this.pass);
    this.authService.login({ email: this.email, password: this.pass })
      .subscribe(
        (response) => {
          this.router.navigate(['/privado']);
        },
        (error) => {
          this.error = true;
          this.email = this.pass = '';
        }
      );
  }
}
