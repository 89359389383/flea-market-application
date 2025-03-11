<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExhibitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'brand_name' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png|max:2048', // 画像ファイル制限: JPEGまたはPNG
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id', // 各カテゴリーが `categories` テーブルに存在するかチェック
            'condition' => 'required',
            'price' => 'required|integer|min:0',
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '商品名を入力してください',
            'name.max' => '商品名は255文字以内で入力してください',
            'brand_name.max' => 'ブランド名は255文字以内で入力してください',
            'description.required' => '商品説明を入力してください',
            'description.max' => '商品説明は255文字以内で入力してください',
            'image.required' => '商品画像をアップロードしてください',
            'image.image' => 'アップロードファイルは画像である必要があります',
            'image.mimes' => '画像ファイルはJPEGまたはPNG形式である必要があります',
            'categories.required' => 'カテゴリーを選択してください',
            'condition.required' => '商品の状態を選択してください',
            'price.required' => '価格を入力してください',
            'price.integer' => '価格は数値である必要があります',
            'price.min' => '価格は0円以上で入力してください',
        ];
    }
}
