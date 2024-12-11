import { Component, OnInit } from '@angular/core';
import { CommonModule } from "@angular/common";
import { HttpClient } from '@angular/common/http';

@Component({
  selector: 'app-privado',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './privado.component.html',
  styleUrl: './privado.component.css'
})
export class PrivadoComponent implements OnInit {
  public mensaje: string = '';
  public error: boolean = false;

  constructor(private http: HttpClient) {}

  ngOnInit () {
    
    this.http.get('http://localhost/final1/public/api/privado')
      .subscribe(
        (response: any) => {
          this.mensaje = response.data;
        },
        (error: any) => {
          this.error = true;
        }
      );
  }
}
