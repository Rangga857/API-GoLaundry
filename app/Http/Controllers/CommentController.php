<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\OrdersLaundries;
use App\Models\ProfilePelanggan; 
use App\Http\Requests\AddCommentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CommentController extends Controller
{
    public function addComment(AddCommentRequest $request)
    {
        try {
            $user = Auth::user(); 

            $profilePelanggan = ProfilePelanggan::where('user_id', $user->user_id)->first();
            if (!$profilePelanggan) {
                return response()->json([
                    'message' => 'Profil pelanggan tidak ditemukan.',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }

            $order = OrdersLaundries::where('id', $request->order_id)
                                    ->where('id_profile', $profilePelanggan->id_profile)
                                    ->first();

            if (!$order) {
                return response()->json([
                    'message' => 'Pesanan tidak ditemukan atau bukan milik Anda.',
                    'status_code' => 404,
                    'data' => null
                ], 404);
            }
            Log::info('Checking order status for order_id: ' . $order->id . ' Status: ' . $order->status);
            if ($order->status !== 'Selesai') {
                return response()->json([
                    'message' => 'Komentar hanya bisa ditambahkan untuk pesanan yang sudah selesai.',
                    'status_code' => 400,
                    'data' => null
                ], 400);
            }

            if (Comment::where('order_id', $request->order_id)->exists()) {
                return response()->json([
                    'message' => 'Anda sudah memberikan komentar untuk pesanan ini.',
                    'status_code' => 409, 
                    'data' => null
                ], 409);
            }
            $comment = Comment::create([
                'order_id' => $request->order_id,
                'user_id' => $user->user_id,
                'comment_text' => $request->comment_text,
            ]);

            return response()->json([
                'message' => 'Komentar berhasil ditambahkan.',
                'status_code' => 201,
                'data' => $comment
            ], 201);

        } catch (\Exception $e) {
            Log::error("Error adding comment: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function getAllComments()
    {
        $user = Auth::user();

        try {
            $comments = Comment::with(['order.profile', 'user']) 
                                ->get()
                                ->map(function ($comment) {
                                    return [
                                        'id' => $comment->id,
                                        'order_id' => $comment->order_id,
                                        'order_status' => $comment->order->status,
                                        'customer_profile_name' => $comment->order->profile->name, 
                                        'comment_text' => $comment->comment_text,
                                        'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                                        'updated_at' => $comment->updated_at->format('Y-m-d H:i:s'),
                                    ];
                                });

            return response()->json([
                'message' => 'Daftar semua komentar.',
                'status_code' => 200,
                'data' => $comments
            ], 200);

        } catch (\Exception $e) {
            Log::error("Error getting all comments: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
    public function getCommentById(int $id)
    {
        $user = Auth::user();

        try {
            $comment = Comment::with(['order.profile', 'user'])->findOrFail($id);

            return response()->json([
                'message' => 'Komentar ditemukan.',
                'status_code' => 200,
                'data' => [
                    'id' => $comment->id,
                    'order_id' => $comment->order_id,
                    'order_status' => $comment->order->status ,
                    'customer_name' => $comment->user->name ,
                    'customer_profile_name' => $comment->order->profile->name,
                    'comment_text' => $comment->comment_text,
                    'created_at' => $comment->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $comment->updated_at->format('Y-m-d H:i:s'),
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Komentar tidak ditemukan.',
                'status_code' => 404,
                'data' => null
            ], 404);
        } catch (\Exception $e) {
            Log::error("Error getting comment by ID: " . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => 'Internal Server Error: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}
