<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
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
            'payment_method' => 'required|in:コンビニ支払い,カード支払い', // enum (2種類のみ)
            'postal_code' => 'required|string|regex:/^\d{3}-\d{4}$/', // ハイフンあり8文字
            'address' => 'required|string|max:255',
            'building' => 'nullable|string|max:255',
        ];
    }

    /**
     * カスタムエラーメッセージの定義
     */
    public function messages()
    {
        return [
            'payment_method.required' => '支払い方法を選択してください',
            'payment_method.in' => '支払い方法は「コンビニ支払い」または「カード支払い」のいずれかを選択してください',
            'postal_code.required' => '郵便番号を入力してください',
            'postal_code.regex' => '郵便番号は「XXX-XXXX」の形式で入力してください',
            'address.required' => '住所を入力してください',
            'building.string' => '建物名は文字列である必要があります',
        ];
    }
}
