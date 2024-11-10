<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use App\Models\Guest;

class GuestController extends Controller {

    /**
    *   Создаем запись гостя
    */
    public function store(Request $request) {
        /**
        *   Выполняем валидацию входных данных
        */
        $validator = Validator::make($request->all(), [
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:guests,email',
            'phone' => 'required|string|unique:guests,phone'
        ]);

        /**
        *   Если мы не прошли валидацию
        */
        if($validator->fails()) {
            /**
            *   Возвращаем список сообщений об ошибкам с кодом 422,
            *   который вполне уместен в данном случае.
            */
            return response()->json($validator->errors(), 422);
        }

        /**
        *   Если валидация была пройдена успешно,
        *   формируем из этого наш объект и пытаемся получить название страны по номеру телефона.
        *
        *   TODO: На данный момент номер телефона получается путем простого сравнение префикса (костыль).
        *   В дальнейшем нужно просто создать таблицу в базе данных со всеми префиксами всех стран и получать название страны
        *   с помощью SQL-запроса или же получать название страны по API, например.
        */
        $entity = $request->all();
        $entity['country'] = $this->getCountryByPhonePrefix($entity['phone']);

        /**
        *   Добавляем гостя в таблицу
        *
        *   TODO: В дальнейшем можно рассмотреть вариант использования транзакций.
        */
        $guest = Guest::create($entity);

        /**
        *   Отдаем результат пользователю
        */
        return response()->json($guest, 201);
    }

    /**
    *   Получаем информацию по гостю
    */
    public function show(int $id) {
        /**
        *   Проверяем, что к нам пришел именно ID
        */
        if(!is_int($id)) {
            /**
            *   Возвращаем сообщение об ошибке
            */
            return response()->json(['error' => 'Invalid identifier'], 422);
        }
        /**
        *   Выполняем запрос к субд, чтобы найти пользователя по ID
        */
        $guest = Guest::find($id);

        /**
        *   Если мы не нашли гостя с таким ID
        */
        if(!$guest) {
            /**
            *   Возвращаем соответствующее сообщение клиенту
            */
            return response()->json(['error' => 'Guest not found'], 404);
        }

        /**
        *   Если всё ок, возвращаем данные о пользователе
        */
        return response()->json($guest);
    }

    /**
    *   Обновляем запись гостя
    */
    public function update(Request $request, int $id) {
        /**
        *   Проверяем, что к нам пришел именно ID
        */
        if(!is_int($id)) {
            /**
            *   Возвращаем сообщение об ошибке
            */
            return response()->json(['error' => 'Invalid identifier'], 422);
        }
        /**
        *   Выполняем запрос к субд, чтобы найти пользователя по ID
        */
        $guest = Guest::find($id);

        /**
        *   Если мы не нашли гостя с таким ID
        */
        if(!$guest) {
            /**
            *   Возвращаем соответствующее сообщение клиенту
            */
            return response()->json(['error' => 'Guest not found'], 404);
        }

        /**
        *   Выполняем валидацию входных данных
        *
        *   sometimes, чтобы выполнять валидацию при условии наличия данного поля - https://laravel.su/docs/11.x/validation
        */
        $validator = Validator::make($request->all(), [
            'firstname' => 'sometimes|required|string|max:255',
            'lastname' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:guests,email',
            'phone' => 'sometimes|required|string|unique:guests,phone'
        ]);

        /**
        *   Если мы не прошли валидацию
        */
        if($validator->fails()) {
            /**
            *   Возвращаем список сообщений об ошибкам с кодом 422,
            *   который вполне уместен в данном случае.
            */
            return response()->json($validator->errors(), 422);
        }

        /**
        *   Так же, как в случае и с методом create, создаем entity и проверяем
        *   был ли изменен номер телефона и пробуем по нему получить название страны.
        */
        $entity = $request->all();

        if(isset($entity['phone'])) {
            $entity['country'] = $this->getCountryByPhonePrefix($entity['phone']);
        }

        /**
        *   Обновляем данные
        */
        $guest->update($entity);

        /**
        *   Возвращаем данные о пользователе
        */
        return response()->json($guest);
    }

    /**
    *   Удаляем гостя по ID
    */
    public function delete(int $id) {
        /**
        *   Проверяем, что к нам пришел именно ID
        */
        if(!is_int($id)) {
            /**
            *   Возвращаем сообщение об ошибке
            */
            return response()->json(['error' => 'Invalid identifier'], 422);
        }
        /**
        *   Выполняем запрос к субд, чтобы найти пользователя по ID
        */
        $guest = Guest::find($id);

        /**
        *   Если мы не нашли гостя с таким ID
        */
        if(!$guest) {
            /**
            *   Возвращаем соответствующее сообщение клиенту
            */
            return response()->json(['error' => 'Guest not found'], 404);
        }

        /**
        *   Удаляем запись из таблицы
        */
        $guest->delete();

        /**
        *   Возвращаем клиенту сообщение о том, что запись была удалена
        */
        return response()->json(['message' => 'Guest deleted successfully']);
    }

    /**
    *   Функция определения страны по коду номера
    *
    *   В дальнейшем либо использовать какой-то API для определения
    *   или создать отдельную таблицу, куда будем складывать префиксы
    *   номеров и названия стран, а в таблице гостей будем хранить id
    *   этой страны из таблицы countries.
    */
    private function getCountryByPhonePrefix($phone) {
        /**
        *   Если в нашей строке есть плюс
        */
        if(preg_match('/^\+/', $phone)) {
            /**
            *   Удаляем его
            */
            $phone = substr($phone, 1, strlen($phone));
        }

        /**
        *   Убираем все пробельные символы
        */
        $phone = trim($phone);

        /**
        *   Список префиксов для разных стран
        */
        $countries = [
            '1' => 'США',
            '2' => 'Великобритания',
            '3' => 'Германия',
            '7' => 'Россия',
            '44' => 'Финляндия',
            '123' => 'Лихтенштейн',
            '971' => 'ОАЭ'
        ];

        /**
        *   Устанавливаем значение $i равным 4 (так как префикс может быть и 4-х значный)
        *   после чего в цикле постепенно уменьшаем его длину до 1 (единицы).
        */
        $i = 4;
        /**
        *   Название страны по номеру телефона (по-умолчанию null)
        */
        $country = null;

        while($i > 0) {
            /**
            *   Здесь мы берем первые три символа номера телефона
            *   и проверяем - есть ли такой префикс в нашем массиве
            *   Если такой префикс есть, то мы просто берем значение из нашего ассоциативного массива (напр. Россия)
            *   и присваиваем его переменной $country, после чего выходим из цикла
            *   Если же нет, уменьшаем префикс на единицу и проверяем уже более короткий его вариант.
            */
            if(isset($countries[substr($phone, 0, $i)])) {
                $country = $countries[substr($phone, 0, $i)];
                break;
            } else {
                $i--;
            }
        }

        return $country;
    }
}