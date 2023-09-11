<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Repositories\MarcaRepository;

class MarcaController extends Controller
{
    public function __construct(Marca $marca) {
        $this->marca = $marca;
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $marcaRepository = new MarcaRepository($this->marca);

        if($request->has('atributos_modelos')) {
            $atributos_modelos = 'modelos:id,'.$request->atributos_modelos;
            $marcaRepository->selectAtributosRegistrosRelacionados($atributos_modelos);
        } else {
            $marcaRepository->selectAtributosRegistrosRelacionados('modelos');
        }

        if($request->has('filtro')) {
            $marcaRepository->filtro($request->filtro);
        }

        if($request->has('atributos')) {
            $marcaRepository->selectAtributos($request->atributos);
        } 

        return response()->json($marcaRepository->getResultado(), 200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //$marca = Marca::create($request->all());

        //stateless o cliente precisa enviar o accept no header do json
        $request->validate($this->marca->rules(), $this->marca->feedback());
    
        //pegando a imagem
        $imagem = $request->file('imagem');
        //gravando local/public/aws gerando urn
        $imagem_urn = $imagem->store('imagens/marcas', 'public');
        //gravando no BD
        $marca = $this->marca->create([
            'nome' => $request->nome,
            'imagem' => $imagem_urn
        ]);

        //$marca = $this->marca->create($request->all());
        
        return response()->json($marca, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param  Integer
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //$marca = $this->marca->find($id); //sem relacionamento
        $marca = $this->marca->with('modelos')->find($id); //com relacionamento
        //dd($marca);
        if ($marca === null) {
            return response()->json(['erro' => 'Recurso pesquisado nao existe'],404);
        }
        
        return response()->json($marca, 200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  Integer
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $marca = $this->marca->find($id);
 
        if($marca === null) {
            return response()->json(['erro' => 'Impossível realizar a atualização. O recurso solicitado não existe'], 404);
        }
 
        if($request->method() === 'PATCH') {
 
            $regrasDinamicas = array();
 
            //percorrendo todas as regras definidas no Model
            foreach($marca->rules() as $input => $regra) {
                
                //coletar apenas as regras aplicáveis aos parâmetros parciais da requisição PATCH
                if(array_key_exists($input, $request->all())) {
                    $regrasDinamicas[$input] = $regra;
                }
            }
            
            $request->validate($regrasDinamicas, $marca->feedback());
 
        } else {
            $request->validate($marca->rules(), $marca->feedback());
        }
 
 
        //remove o arquivo antigo caso um novo arquivo tenha sido enviado no request
        if($request->file('imagem') != null) {
            // Deleta a imagem atual
            Storage::disk('public')->delete($marca->imagem);
            // salvando caminho da nova imagem ao objeto $marca
            $imagem = $request->file('imagem');
            $imagem_urn = $imagem->store('imagens/marcas', 'public');
            // atribuindo novo caminho da nova imagem ao objeto $marca
            $marca->imagem = $imagem_urn;
        } else {
            $imagem_urn = $marca->imagem;
        }
        $marca->fill($request->all());
        $marca->imagem = $imagem_urn;
 
        // salvando dados
        $marca->save();
 
        return response()->json($marca, 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  Integer
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $marca = $this->marca->find($id);

        if ($marca === null) {
            return response()->json(['erro' => 'Nao foi Possivel Excluir! Recurso informado nao existe'],404);
        }

        Storage::disk('public')->delete($marca->imagem);
 
        $marca->delete();
        return response()->json(['msg' => 'A marca foi removida com sucesso!'], 200);
        
    }
}