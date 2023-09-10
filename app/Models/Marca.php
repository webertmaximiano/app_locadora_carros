<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Marca extends Model
{
    use HasFactory;
    protected $fillable = ['nome', 'imagem'];

    public function rules() {
        return [
            'nome' => 'required|unique:marcas,nome,'.$this->id.'|min:3',
            'imagem' => 'required|file|mimes:png,jpg,jpeg,svg,webp'
        ]; 
        /* regras validacao nome
         1- Tabela, 2- nome da coluna, 3- id do registro atual para ser desconsiderado 
         */
    }

    public function feedback() {
        return [
            'required' => 'O campo :attribute e obrigatorio',
            'imagem.mimes' => 'O tipo de arquivo nao e valido',
            'nome.unique' => 'O nome da marca ja existe',
            'nome.min' => 'O nome deve ter no minimo 3 caracteres'
        ];
    }
    //Criando relacionamento Marca com Modelos
    public function modelos() {
        //UMA marca POSSUI MUITOS modelos
        return $this->hasMany('App\Models\Modelo');
    }
}
